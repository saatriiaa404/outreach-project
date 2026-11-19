{{-- PDF Mustahik, template v4 (D-074): A4 portrait like every printed document; the layout of docs/reference/mustahik.pdf scaled to the A4 width (180 mm, margins 15 mm), the Baznas logo twice the v3 size, and the place, date and Surveyor block centred; labels literal (D-022); the score column only holds a check (D-053). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: a4 portrait; margin: 7mm 15mm 6mm 15mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 6.6pt; color: #000; line-height: 1.05; }
        .check { font-family: "DejaVu Sans", sans-serif; font-size: 6pt; line-height: 1; }
        .logo { text-align: center; height: 46mm; }
        .logo img { height: 46mm; }
        .heading { text-align: center; font-weight: bold; font-size: 7.5pt; margin: 1.5mm 0 0; }
        .heading p { margin: 0; }
        table.header { border-collapse: collapse; width: 180mm; margin-top: 3mm; font-size: 7pt; }
        table.header td { padding: 0; vertical-align: top; }
        table.header tr.sizer td { height: 0; line-height: 0; font-size: 0; }
        tr.wide td { height: 3.9mm; }
        tr.tight td { height: 3.1mm; }
        td.h-label { width: 60.4mm; }
        td.h-colon { width: 7.7mm; }
        .bold { font-weight: bold; }
        table.q { width: 180mm; border-collapse: collapse; margin-top: 2mm; }
        table.q td { border: 0.6pt solid #000; padding: 0.1mm 0.6mm; vertical-align: top; }
        table.q td.gap, table.q td.free { border: none; padding: 0; }
        /* A zero-height first row fixes the column widths, which Dompdf does not take from colspans. */
        table.q tr.sizer td { height: 0; padding: 0; border: none; line-height: 0; font-size: 0; }
        td.q-no { text-align: center; vertical-align: middle; }
        td.q-score { text-align: center; }
        tr.q-title td.q-text { font-weight: bold; }
        .statement { border: 0.75pt solid #000; height: 11mm; margin: 8mm 0 0 0; text-align: center; font-weight: bold; font-size: 7.5pt; padding: 2.5mm 4mm 0; }
        .signature { margin-top: 12mm; font-size: 7.5pt; text-align: center; }
        .signature p { margin: 0; }
        .signature .space { height: 20mm; }
        .signature img { max-height: 20mm; max-width: 50mm; }
    </style>
</head>
<body>
    <div class="logo"><img src="{{ $logo }}" alt=""></div>
    <div class="heading">
        <p>FORM SURVEY MUSTAHIK</p>
        <p>BADAN AMIL ZAKAT NASIONAL KOTA SURABAYA</p>
    </div>

    <table class="header">
        <tr class="sizer"><td style="width: 60.4mm"></td><td style="width: 7.7mm"></td><td style="width: 111.9mm"></td></tr>
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
            @foreach ([['36.8mm', '8.1mm'], ['36.6mm', '12mm'], ['40.3mm', '8.6mm']] as $i => [$text, $score])
                @if ($i > 0)
                    <td class="gap" style="width: {{ $i === 1 ? '5.6mm' : '4.1mm' }}"></td>
                @endif
                <td style="width: 4.9mm"></td><td style="width: 4.35mm"></td><td style="width: {{ $text }}"></td><td style="width: {{ $score }}"></td>
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
                                    <p>Surveyor</p>
                                    <div class="space">
                                        @if ($signature['image'])
                                            <img src="{{ $signature['image'] }}" alt="">
                                        @endif
                                    </div>
                                    <p>{{ $signature['name'] }}</p>
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
