<?php
// generate/DocxWriter.php — Generator DOCX tanpa library eksternal
// Mendukung: ZipArchive (jika tersedia) ATAU Pure PHP ZIP (fallback)
// Kompatibel dengan shared hosting / free hosting

// =============================================
// Pure PHP ZIP Writer (tidak butuh ekstensi zip)
// =============================================
class PureZip {
    private array $files   = [];
    private array $offsets = [];

    public function addFromString(string $name, string $data): void {
        $this->files[$name] = $data;
    }

    public function build(): string {
        $localPart  = '';
        $centralDir = '';
        $offset     = 0;

        foreach ($this->files as $name => $data) {
            $crc      = crc32($data);
            $size     = strlen($data);
            $nameLen  = strlen($name);
            $dosTime  = $this->dosTime();

            // Local file header
            $local  = "\x50\x4b\x03\x04";          // Local file header signature
            $local .= pack('v', 20);                  // Version needed (2.0)
            $local .= pack('v', 0);                   // General purpose bit flag
            $local .= pack('v', 0);                   // Compression method: STORED
            $local .= pack('V', $dosTime);            // Last mod time & date
            $local .= pack('V', $crc);                // CRC-32
            $local .= pack('V', $size);               // Compressed size
            $local .= pack('V', $size);               // Uncompressed size
            $local .= pack('v', $nameLen);            // Filename length
            $local .= pack('v', 0);                   // Extra field length
            $local .= $name;
            $local .= $data;

            $this->offsets[$name] = $offset;
            $offset += strlen($local);
            $localPart .= $local;

            // Central directory entry
            $centralDir .= "\x50\x4b\x01\x02";       // Central dir signature
            $centralDir .= pack('v', 20);             // Version made by
            $centralDir .= pack('v', 20);             // Version needed
            $centralDir .= pack('v', 0);              // General purpose bit flag
            $centralDir .= pack('v', 0);              // Compression method: STORED
            $centralDir .= pack('V', $dosTime);       // Last mod time & date
            $centralDir .= pack('V', $crc);           // CRC-32
            $centralDir .= pack('V', $size);          // Compressed size
            $centralDir .= pack('V', $size);          // Uncompressed size
            $centralDir .= pack('v', $nameLen);       // Filename length
            $centralDir .= pack('v', 0);              // Extra field length
            $centralDir .= pack('v', 0);              // File comment length
            $centralDir .= pack('v', 0);              // Disk number start
            $centralDir .= pack('v', 0);              // Internal file attributes
            $centralDir .= pack('V', 0);              // External file attributes
            $centralDir .= pack('V', $this->offsets[$name]); // Relative offset
            $centralDir .= $name;
        }

        $numFiles = count($this->files);
        $cdSize   = strlen($centralDir);
        $cdOffset = $offset;

        // End of central directory record
        $eocd  = "\x50\x4b\x05\x06";                 // EOCD signature
        $eocd .= pack('v', 0);                        // Disk number
        $eocd .= pack('v', 0);                        // Disk with central dir
        $eocd .= pack('v', $numFiles);                // Entries on this disk
        $eocd .= pack('v', $numFiles);                // Total entries
        $eocd .= pack('V', $cdSize);                  // Central directory size
        $eocd .= pack('V', $cdOffset);                // Central directory offset
        $eocd .= pack('v', 0);                        // Comment length

        return $localPart . $centralDir . $eocd;
    }

    private function dosTime(): int {
        $t = getdate();
        return (($t['year'] - 1980) << 25)
             | ($t['mon']     << 21)
             | ($t['mday']    << 16)
             | ($t['hours']   << 11)
             | ($t['minutes'] <<  5)
             | ($t['seconds']  >>  1);
    }
}

// =============================================
// DocxWriter — Main Class
// =============================================
class DocxWriter {
    private array  $bodyParts  = [];
    private int    $rIdCounter = 3; // rId1=styles, rId2=settings, rId3+ = images
    private array  $images     = []; // ['rId' => 'rId3', 'ext' => 'jpg', 'data' => '<base64 binary>']

    // =============================================
    // Public API
    // =============================================

    /** Paragraf teks biasa */
    public function addParagraph(string $text, array $opts = []): self {
        $this->bodyParts[] = $this->buildParagraph($text, $opts);
        return $this;
    }

    /** Baris kosong */
    public function addBlankLine(int $count = 1): self {
        for ($i = 0; $i < $count; $i++) {
            $this->bodyParts[] = '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr></w:p>';
        }
        return $this;
    }

    /** Tabel */
    public function addTable(array $headers, array $rows, array $opts = []): self {
        if (empty($headers) && empty($rows)) return $this;

        $colWidths = $opts['colWidths'] ?? [];
        $noBorder  = $opts['noBorder']  ?? false;
        $xml  = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblStyle w:val="TableGrid"/>';
        $xml .= '<w:tblW w:w="9360" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        foreach (['top','left','bottom','right','insideH','insideV'] as $b) {
            if ($noBorder) {
                $xml .= '<w:' . $b . ' w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
            } else {
                $xml .= '<w:' . $b . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
            }
        }
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';


        // Header row — hanya tampilkan jika ada teks header (bukan semua kosong)
        $hasHeader = array_filter($headers, fn($h) => trim((string)$h) !== '');
        if (!empty($hasHeader)) {
            $xml .= '<w:tr>';
            foreach ($headers as $i => $h) {
                $w    = !empty($colWidths[$i]) ? $colWidths[$i] : intdiv(9360, max(count($headers), 1));
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>';
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="FFFFFF"/></w:tcPr>';
                $xml .= $this->buildParagraph($h, ['bold' => true, 'color' => '000000', 'align' => 'center', 'size' => 18]);
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        // Data rows
        foreach ($rows as $ri => $row) {
            $xml .= '<w:tr>';
            foreach ($row as $ci => $cell) {
                $w        = !empty($colWidths[$ci]) ? $colWidths[$ci] : intdiv(9360, max(count($headers), 1));
                $cellOpts = is_array($cell) ? ($cell['opts'] ?? []) : [];
                $cellText = is_array($cell) ? ($cell['text'] ?? '') : (string)$cell;
                $xml .= '<w:tc>';
                $xml .= '<w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>';
                $xml .= '<w:shd w:val="clear" w:color="auto" w:fill="FFFFFF"/></w:tcPr>';
                $xml .= $this->buildParagraph($cellText, array_merge(['size' => 18], $cellOpts));
                $xml .= '</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        $xml .= '</w:tbl>';
        $this->bodyParts[] = $xml;
        return $this;
    }

    /**
     * Embed gambar ke DOCX dan kembalikan rId-nya
     * @param string $filePath  Path absolut ke file gambar (jpg/png)
     * @return string  rId yang digunakan untuk referensi di XML
     */
    public function embedImage(string $filePath): string {
        if (!file_exists($filePath)) return '';
        $ext   = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $data  = file_get_contents($filePath);
        if ($data === false || strlen($data) === 0) return '';
        $rId   = 'rId' . $this->rIdCounter++;
        $this->images[] = [
            'rId'  => $rId,
            'ext'  => $ext,
            'data' => $data,
            'path' => 'word/media/img_' . count($this->images) . '.' . $ext,
        ];
        return $rId;
    }

    /**
     * Tambah gambar inline ke dokumen
     * @param string $rId   rId dari embedImage()
     * @param int    $cx    Lebar dalam EMU (English Metric Units). 1cm = 360000 EMU
     * @param int    $cy    Tinggi dalam EMU
     */
    public function addInlineImage(string $rId, int $cx = 1440000, int $cy = 1440000, string $align = 'center'): self {
        if (empty($rId)) return $this;
        $imgIdx = count($this->images) - 1; // last embedded
        // Find the correct index by rId
        foreach ($this->images as $i => $img) {
            if ($img['rId'] === $rId) { $imgIdx = $i; break; }
        }
        $drawingId = $imgIdx + 1;
        $xml  = '<w:p><w:pPr><w:jc w:val="' . htmlspecialchars($align) . '"/><w:spacing w:before="0" w:after="0"/></w:pPr>';
        $xml .= '<w:r><w:rPr/><w:drawing>';
        $xml .= '<wp:inline distT="0" distB="0" distL="0" distR="0">';
        $xml .= '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>';
        $xml .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
        $xml .= '<wp:docPr id="' . $drawingId . '" name="Image' . $drawingId . '"/>';
        $xml .= '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>';
        $xml .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
        $xml .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $xml .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
        $xml .= '<pic:nvPicPr>';
        $xml .= '<pic:cNvPr id="' . $drawingId . '" name="Image' . $drawingId . '"/>';
        $xml .= '<pic:cNvPicPr/>';
        $xml .= '</pic:nvPicPr>';
        $xml .= '<pic:blipFill>';
        $xml .= '<a:blip r:embed="' . $rId . '"/>';
        $xml .= '<a:stretch><a:fillRect/></a:stretch>';
        $xml .= '</pic:blipFill>';
        $xml .= '<pic:spPr>';
        $xml .= '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>';
        $xml .= '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>';
        $xml .= '</pic:spPr>';
        $xml .= '</pic:pic>';
        $xml .= '</a:graphicData>';
        $xml .= '</a:graphic>';
        $xml .= '</wp:inline>';
        $xml .= '</w:drawing></w:r></w:p>';
        $this->bodyParts[] = $xml;
        return $this;
    }

    /**
     * Tambah kop surat resmi: logo di kiri, teks instansi di kanan
     * Menggunakan tabel 2 kolom tanpa border
     */
    public function addKopWithLogo(
        string $logoPath,
        string $namaInstansi,
        string $kanwil,
        string $alamat,
        string $telp,
        string $fax,
        string $email,
        string $laman   = 'lapastasikmalaya.kemenkumham.go.id',
        string $kodePos = '46112'
    ): self {
        $rId = $this->embedImage($logoPath);

        // Cari img index untuk rId ini
        $logoExists = !empty($rId) && file_exists($logoPath);

        // Gambar logo ~2.8cm x 2.8cm = 1008000 EMU x 1008000 EMU
        $logoCx = 1008000;
        $logoCy = 1008000;

        // Bangun cell kiri: logo
        $leftCell  = '<w:tc>';
        $leftCell .= '<w:tcPr>';
        $leftCell .= '<w:tcW w:w="1440" w:type="dxa"/>';
        $leftCell .= '<w:tcBorders>';
        foreach (['top','left','bottom','right'] as $b) {
            $leftCell .= '<w:' . $b . ' w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        }
        $leftCell .= '</w:tcBorders>';
        $leftCell .= '<w:vAlign w:val="center"/>';
        $leftCell .= '</w:tcPr>';

        if ($logoExists) {
            $drawingId = count($this->images);
            $leftCell .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr>';
            $leftCell .= '<w:r><w:rPr/><w:drawing>';
            $leftCell .= '<wp:inline distT="0" distB="0" distL="0" distR="0">';
            $leftCell .= '<wp:extent cx="' . $logoCx . '" cy="' . $logoCy . '"/>';
            $leftCell .= '<wp:effectExtent l="0" t="0" r="0" b="0"/>';
            $leftCell .= '<wp:docPr id="' . $drawingId . '" name="Logo' . $drawingId . '"/>';
            $leftCell .= '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>';
            $leftCell .= '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">';
            $leftCell .= '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">';
            $leftCell .= '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">';
            $leftCell .= '<pic:nvPicPr><pic:cNvPr id="' . $drawingId . '" name="Logo' . $drawingId . '"/><pic:cNvPicPr/></pic:nvPicPr>';
            $leftCell .= '<pic:blipFill><a:blip r:embed="' . $rId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>';
            $leftCell .= '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $logoCx . '" cy="' . $logoCy . '"/></a:xfrm>';
            $leftCell .= '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>';
            $leftCell .= '</pic:pic></a:graphicData></a:graphic>';
            $leftCell .= '</wp:inline></w:drawing></w:r></w:p>';
        } else {
            $leftCell .= '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr></w:p>';
        }
        $leftCell .= '</w:tc>';

        // Bangun cell kanan: teks instansi
        $rightCell  = '<w:tc>';
        $rightCell .= '<w:tcPr>';
        $rightCell .= '<w:tcW w:w="7920" w:type="dxa"/>';
        $rightCell .= '<w:tcBorders>';
        foreach (['top','left','bottom','right'] as $b) {
            $rightCell .= '<w:' . $b . ' w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        }
        $rightCell .= '</w:tcBorders>';
        $rightCell .= '<w:vAlign w:val="center"/>';
        $rightCell .= '</w:tcPr>';

        // Baris-baris teks kop
        $addrLine1 = trim($alamat) . ', Kode Pos ' . ($kodePos ?: '46112') . ' Telp. ' . $telp;
        $addrLine2 = ($laman ? 'Laman : ' . $laman . ' ' : '') . 'Pos-el : ' . $email;
        $textRows = [
            ['text' => 'KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', 'bold' => false, 'size' => 18],
            ['text' => 'DIREKTORAT JENDERAL PEMASYARAKATAN',                          'bold' => false, 'size' => 18],
            ['text' => $kanwil,                                                       'bold' => false, 'size' => 18],
            ['text' => $namaInstansi,                                                 'bold' => true,  'size' => 22],
            ['text' => $addrLine1,                                                    'bold' => false, 'size' => 16],
            ['text' => $addrLine2,                                                    'bold' => false, 'size' => 16],
        ];
        foreach ($textRows as $row) {
            $rightCell .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="20"/></w:pPr>';
            $rightCell .= '<w:r><w:rPr>';
            if ($row['bold']) $rightCell .= '<w:b/><w:bCs/>';
            $rightCell .= '<w:sz w:val="' . $row['size'] . '"/><w:szCs w:val="' . $row['size'] . '"/>';
            $rightCell .= '<w:lang w:val="id-ID"/>';
            $rightCell .= '</w:rPr>';
            $rightCell .= '<w:t xml:space="preserve">' . htmlspecialchars($row['text']) . '</w:t>';
            $rightCell .= '</w:r></w:p>';
        }
        $rightCell .= '</w:tc>';

        // Tabel KOP (tanpa border apapun — cell border override table border)
        $xml  = '<w:tbl>';
        $xml .= '<w:tblPr>';
        $xml .= '<w:tblW w:w="9360" w:type="dxa"/>';
        $xml .= '<w:tblBorders>';
        // Semua border none — garis KOP dibuat sebagai paragraph terpisah di bawah
        $xml .= '<w:top    w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '<w:left   w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '<w:right  w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml .= '</w:tblBorders>';
        $xml .= '</w:tblPr>';
        $xml .= '<w:tr>' . $leftCell . $rightCell . '</w:tr>';
        $xml .= '</w:tbl>';
        $this->bodyParts[] = $xml;

        // Garis bawah kop surat — sebagai paragraph border (TIDAK bisa di-override cell)
        // w:sz="24" = 3pt tebal, sesuai template resmi lapas
        $this->bodyParts[] = '<w:p>'
            . '<w:pPr>'
            .   '<w:pBdr>'
            .     '<w:bottom w:val="single" w:sz="24" w:space="1" w:color="000000"/>'
            .   '</w:pBdr>'
            .   '<w:spacing w:before="0" w:after="60"/>'
            . '</w:pPr>'
            . '</w:p>';

        return $this;
    }


    /** Section break untuk halaman baru */
    public function addPageBreak(): self {
        $this->bodyParts[] = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
        return $this;
    }

    /** Stream ke browser sebagai file download */
    public function download(string $filename = 'document.docx'): void {
        // Bangun konten DOCX (zip) sepenuhnya di memory
        $zipData = $this->buildZipInMemory();

        if (empty($zipData)) {
            die('<div style="font-family:Arial;padding:40px;background:#fff0f0;border:2px solid red;border-radius:8px;">
                <h2 style="color:red;">❌ Gagal membuat file DOCX</h2>
                <p>Tidak ada konten yang bisa diproses.</p>
            </div>');
        }

        // Bersihkan output buffer agar file tidak korup
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
        header('Content-Length: ' . strlen($zipData));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $zipData;
        exit;
    }

    /** Simpan ke file (untuk testing CLI) */
    public function saveToFile(string $filePath): bool {
        $zipData = $this->buildZipInMemory();
        if (empty($zipData)) return false;
        return file_put_contents($filePath, $zipData) !== false;
    }

    // =============================================
    // Private Helpers
    // =============================================

    private function buildParagraph(string $text, array $opts = []): string {
        $align     = $opts['align']       ?? 'left';
        $bold      = !empty($opts['bold']);
        $italic    = !empty($opts['italic']);
        $underline = !empty($opts['underline']);
        $size      = (int)($opts['size']  ?? 22);
        $color     = $opts['color']       ?? '000000';
        $indent    = isset($opts['indent']) ? '<w:ind w:firstLine="' . $opts['indent'] . '"/>' : '';
        $before    = (int)($opts['before'] ?? 60);
        $after     = (int)($opts['after']  ?? 60);
        $lines     = explode("\n", $text);

        $xml  = '<w:p>';
        $xml .= '<w:pPr>';
        $xml .= '<w:jc w:val="' . htmlspecialchars($align) . '"/>';
        $xml .= '<w:spacing w:before="' . $before . '" w:after="' . $after . '" w:line="276" w:lineRule="auto"/>';
        if ($indent) $xml .= $indent;
        $xml .= '</w:pPr>';

        foreach ($lines as $li => $line) {
            if ($li > 0) {
                $xml .= '<w:r><w:br/></w:r>';
            }
            $xml .= '<w:r><w:rPr>';
            if ($bold)      $xml .= '<w:b/><w:bCs/>';
            if ($italic)    $xml .= '<w:i/><w:iCs/>';
            if ($underline) $xml .= '<w:u w:val="single"/>';
            $xml .= '<w:sz w:val="' . $size . '"/>';
            $xml .= '<w:szCs w:val="' . $size . '"/>';
            $xml .= '<w:color w:val="' . htmlspecialchars($color) . '"/>';
            $xml .= '<w:lang w:val="id-ID"/>';
            $xml .= '</w:rPr>';
            $xml .= '<w:t xml:space="preserve">' . htmlspecialchars($line) . '</w:t>';
            $xml .= '</w:r>';
        }

        $xml .= '</w:p>';
        return $xml;
    }

    /**
     * Bangun file ZIP di memory (tidak butuh temp file / ZipArchive)
     */
    private function buildZipInMemory(): string {
        $bodyContent = implode('', $this->bodyParts);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
        xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
        xmlns:v="urn:schemas-microsoft-com:vml"
        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
        xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
        mc:Ignorable="w14">
<w:body>' . $bodyContent . '
<w:sectPr>
<w:pgSz w:w="12240" w:h="15840"/>
<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1701" w:header="720" w:footer="720" w:gutter="0"/>
</w:sectPr>
</w:body>
</w:document>';

        // Bangun content types dengan support gambar
        $imageContentTypes = '';
        $usedExts = [];
        foreach ($this->images as $img) {
            if (!in_array($img['ext'], $usedExts)) {
                $mime = ($img['ext'] === 'png') ? 'image/png' : 'image/jpeg';
                $imageContentTypes .= '<Default Extension="' . $img['ext'] . '" ContentType="' . $mime . '"/>';
                $usedExts[] = $img['ext'];
            }
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml"  ContentType="application/xml"/>' . $imageContentTypes . '
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
<Override PartName="/word/styles.xml"   ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

        // Bangun word rels dengan gambar
        $imageRels = '';
        foreach ($this->images as $img) {
            $imgFilename = basename($img['path']);
            $imageRels .= '<Relationship Id="' . $img['rId'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/' . $imgFilename . '"/>';
        }
        $wordRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>' . $imageRels . '
</Relationships>';

        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<w:docDefaults>
<w:rPrDefault><w:rPr>
<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>
<w:sz w:val="22"/><w:szCs w:val="22"/>
<w:lang w:val="id-ID" w:eastAsia="id-ID" w:bidi="ar-SA"/>
</w:rPr></w:rPrDefault>
<w:pPrDefault><w:pPr>
<w:spacing w:after="120" w:line="276" w:lineRule="auto"/>
</w:pPr></w:pPrDefault>
</w:docDefaults>
<w:style w:type="table" w:styleId="TableGrid">
<w:name w:val="Table Grid"/>
<w:basedOn w:val="TableNormal"/>
<w:tblPr><w:tblBorders>
<w:top    w:val="single" w:sz="4" w:space="0" w:color="auto"/>
<w:left   w:val="single" w:sz="4" w:space="0" w:color="auto"/>
<w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>
<w:right  w:val="single" w:sz="4" w:space="0" w:color="auto"/>
<w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>
<w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>
</w:tblBorders></w:tblPr>
</w:style>
</w:styles>';

        $settingsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:defaultTabStop w:val="708"/>
</w:settings>';

        // Gunakan PureZip jika /tmp tidak bisa ditulis (shared hosting)
        // atau ZipArchive jika /tmp tersedia
        $tmpWritable = is_writable(sys_get_temp_dir());

        if (class_exists('ZipArchive') && $tmpWritable) {
            // Via ZipArchive ke temp file lalu baca ke memory
            $tmpFile = tempnam(sys_get_temp_dir(), 'docx_');
            if ($tmpFile !== false) {
                $zip = new ZipArchive();
                if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    $zip->addFromString('[Content_Types].xml',          $contentTypes);
                    $zip->addFromString('_rels/.rels',                  $rels);
                    $zip->addFromString('word/document.xml',            $documentXml);
                    $zip->addFromString('word/_rels/document.xml.rels', $wordRels);
                    $zip->addFromString('word/styles.xml',              $stylesXml);
                    $zip->addFromString('word/settings.xml',            $settingsXml);
                    foreach ($this->images as $img) {
                        $zip->addFromString($img['path'], $img['data']);
                    }
                    $zip->close();
                    $data = file_get_contents($tmpFile);
                    @unlink($tmpFile);
                    if ($data !== false && strlen($data) > 0) return $data;
                }
                @unlink($tmpFile);
            }
        }

        // Pure PHP ZIP — tidak butuh temp file, tidak butuh ekstensi zip
        // Digunakan di shared hosting yang /tmp-nya tidak bisa ditulis
        $pzip = new PureZip();
        $pzip->addFromString('[Content_Types].xml',          $contentTypes);
        $pzip->addFromString('_rels/.rels',                  $rels);
        $pzip->addFromString('word/document.xml',            $documentXml);
        $pzip->addFromString('word/_rels/document.xml.rels', $wordRels);
        $pzip->addFromString('word/styles.xml',              $stylesXml);
        $pzip->addFromString('word/settings.xml',            $settingsXml);
        foreach ($this->images as $img) {
            $pzip->addFromString($img['path'], $img['data']);
        }
        return $pzip->build();
    }
}
