{{-- PDF Mustahik, template v3 (D-070): Legal paper like docs/reference/mustahik.pdf, with the logo, header block and three question tables at the measured positions and widths; labels literal (D-022); the score column only holds a check (D-053). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: legal portrait; margin: 1mm 16.5mm 8mm 13.2mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7.4pt; color: #000; line-height: 1.2; }
        .check { font-family: "DejaVu Sans", sans-serif; }
        .logo { text-align: center; height: 32mm; }
        .logo img { height: 32mm; }
        .heading { text-align: center; font-weight: bold; font-size: 7.5pt; margin: -1mm 0 0; }
        .heading p { margin: 0; }
        table.header { border-collapse: collapse; width: 186mm; margin-top: 14mm; font-size: 7.5pt; }
        table.header td { padding: 0; vertical-align: top; }
        table.header tr.sizer td { height: 0; line-height: 0; font-size: 0; }
        tr.wide td { height: 5.3mm; }
        tr.tight td { height: 3.6mm; }
        td.h-label { width: 62.5mm; }
        td.h-colon { width: 8mm; }
        .bold { font-weight: bold; }
        table.q { width: 186.2mm; border-collapse: collapse; margin-top: 2.5mm; }
        table.q td { border: 0.6pt solid #000; padding: 0.35mm 0.6mm; vertical-align: top; }
        table.q td.gap, table.q td.free { border: none; padding: 0; }
        /* A zero-height first row fixes the column widths, which Dompdf does not take from colspans. */
        table.q tr.sizer td { height: 0; padding: 0; border: none; line-height: 0; font-size: 0; }
        td.q-no { text-align: center; vertical-align: middle; }
        td.q-score { text-align: center; }
        tr.q-title td.q-text { font-weight: bold; }
        .statement { border: 0.75pt solid #000; height: 13mm; margin: 12mm 0 0 0; text-align: center; font-weight: bold; font-size: 7.5pt; padding: 2.5mm 4mm 0; }
        .signature { margin-top: 18mm; font-size: 7.5pt; }
        .signature p { margin: 0; }
        .signature .center { text-align: center; }
        .signature .space { height: 22mm; text-align: center; }
        .signature img { max-height: 22mm; max-width: 50mm; }
    </style>
</head>
<body>
    <div class="logo"><img src="{{ $logo }}" alt=""></div>
    <div class="heading">
        <p>FORM SURVEY MUSTAHIK</p>
        <p>BADAN AMIL ZAKAT NASIONAL KOTA SURABAYA</p>
    </div>

    <table class="header">
        <tr class="sizer"><td style="width: 62.5mm"></td><td style="width: 8mm"></td><td style="width: 115.5mm"></td></tr>
        <tr class="wide"><td class="h-label">Nama Petugas</td><td class="h-colon">:</td><td>{{ $header['petugas'] }}</td></tr>
        <tr class="wide"><td class="h-label">Tanggal /Hari (Survey)</td><td class="h-colon">:</td><td>{{ $header['survey'] }}</td></tr>
        <tr class="wide"><td class="h-label">Kelurahan</td><td class="h-colon">:</td><td>{{ $header['kelurahan'] }}</td></tr>
        <tr class="wide"><td class="h-label">Kecamatan</td><td class="h-colon">:</td><td>{{ $header['kecamatan'] }}</td></tr>
        <tr class="wide"><td class="h-label bold" colspan="3">DATA KEPALA KELUARGA</td></tr>
        <tr class="tight"><td class="h-label">Nama</td><td class="h-colon">:</td><td>{{ $header['kk_nama'] }}</td></tr>
        <tr class="tight"><td class="h-label">NIK / No KK</td><td class="h-colon">:</td><td>{{ $header['kk_nik'] }}</td></tr>
        <tr class="tight">
            <td class="h-label">Alamat</td><td class="h-colon">:</td>
            <td>Jalan {{ $header['kk_alamat'] }} &nbsp; Kelurahan {{ $header['kelurahan'] }}</td>
        </tr>
        <tr class="wide"><td></td><td></td><td>Kecamatan {{ $header['kecamatan'] }} &nbsp; RT {{ $header['kk_rt'] }} &nbsp; RW. {{ $header['kk_rw'] }}</td></tr>
        <tr class="wide"><td class="h-label">Nomor Telepon HP</td><td class="h-colon">:</td><td>{{ $header['no_hp'] }}</td></tr>
    </table>

    {{-- One table like the source form, so question k of A, B and C share a row height. --}}
    @php($rows = max(array_map(fn ($section) => count($section['questions']), $sections)))
    @php($lastC = count($sections[2]['questions']))
    <table class="q">
        <tr class="sizer">
            @foreach ([['38.1mm', '8.4mm'], ['37.9mm', '12.4mm'], ['41.7mm', '8.9mm']] as $i => [$text, $score])
                @if ($i > 0)
                    <td class="gap" style="width: {{ $i === 1 ? '5.8mm' : '4.2mm' }}"></td>
                @endif
                <td style="width: 5.1mm"></td><td style="width: 4.5mm"></td><td style="width: {{ $text }}"></td><td style="width: {{ $score }}"></td>
            @endforeach
        </tr>
        <tr class="q-title">
            @foreach ($sections as $index => $section)
                @if ($index > 0)
                    <td class="gap"></td>
                @endif
                <td class="q-text" colspan="3">{{ $section['title'] }}</td>
                <td class="q-score bold">{{ $index === 0 ? '' : 'score' }}</td>
            @endforeach
        </tr>
        @for ($k = 0; $k < $rows; $k++)
            @for ($line = 0; $line < 5; $line++)
                <tr class="{{ $line === 0 ? 'q-title' : '' }}">
                    @foreach ($sections as $index => $section)
                        @if ($index > 0)
                            <td class="gap"></td>
                        @endif
                        @php($question = $section['questions'][$k] ?? null)
                        @if ($question !== null)
                            @if ($line === 0)
                                <td class="q-no" rowspan="5">{{ $question['no'] }}</td>
                                <td class="q-text" colspan="2">{{ $question['label'] }}</td>
                                <td class="q-score"></td>
                            @else
                                @php($option = $question['options'][$line - 1] ?? null)
                                <td class="q-letter">{{ $option ? $option['letter'].'.' : '' }}</td>
                                <td>{{ $option['label'] ?? '' }}</td>
                                <td class="q-score"><span class="check">{{ ($option['checked'] ?? false) ? '✓' : '' }}</span></td>
                            @endif
                        @elseif ($index === 2 && $k === $lastC && $line === 0)
                            {{-- Under the last C question: the statement and the Surveyor signature. --}}
                            <td class="free" colspan="4" rowspan="{{ ($rows - $lastC) * 5 }}">
                                <div class="statement">Demikian formulir ini saya isi dengan sesunguhnya.</div>
                                <div class="signature">
                                    <p>Surabaya, {{ $signature['date'] }}</p>
                                    <p class="center">Surveyor</p>
                                    <div class="space">
                                        @if ($signature['image'])
                                            <img src="{{ $signature['image'] }}" alt="">
                                        @endif
                                    </div>
                                    <p class="center">{{ $signature['name'] }}</p>
                                </div>
                            </td>
                        @elseif ($index !== 2)
                            <td class="free" colspan="4"></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        @endfor
    </table>
</body>
</html>
