{{-- PDF Had Kifayah, template v3 (D-070): A4 like docs/reference/kifayah.pdf, logo and tables at the measured positions and widths (content 161.4 mm from 25.6 mm, rows 4.2 mm); options literal (D-037); expense boxes ticked when the amount is not 0 (D-064). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: a4 portrait; margin: 1mm 23mm 22mm 25.6mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #000; line-height: 1.15; }
        .check { font-family: "DejaVu Sans", sans-serif; }
        .logo { text-align: center; height: 32mm; }
        .logo img { height: 32mm; }
        h1 { text-align: center; font-size: 10.5pt; margin: 7mm 0 0; }
        h2 { font-size: 10pt; margin: 4mm 0 1.5mm; }
        h2.tight { margin-top: 0.8mm; }
        table { border-collapse: collapse; width: 161.4mm; }
        table td { padding: 0.45mm 1.2mm; vertical-align: top; }
        .b { border: 0.75pt solid #000; }
        /* A zero-height first row fixes the column widths; Dompdf ignores <col> widths. */
        tr.sizer td { height: 0; padding: 0; line-height: 0; font-size: 0; }
        .caption td { padding-left: 2.3mm; }
        td.colon { text-align: center; padding: 0; }
        td.box { text-align: center; padding: 0; color: #444; }
        td.check { text-align: center; padding: 0; }
        .note { text-align: right; font-size: 7pt; margin: 0.8mm 0 0; }
        .page-break { page-break-before: always; }
        .statement { margin-top: 8mm; text-align: justify; line-height: 1.3; }
        .signature { margin-top: 8mm; }
        .signature p { margin: 0; }
        .signature .space { height: 24mm; }
        .signature img { max-height: 24mm; max-width: 70mm; }
    </style>
</head>
<body>
    <div class="logo"><img src="{{ $logo }}" alt=""></div>
    <h1>FORM HAD KIFAYAH</h1>

    <h2>INFORMASI MUSTAHIK</h2>
    <table>
        <tr class="sizer"><td style="width: 47.1mm"></td><td style="width: 7.8mm"></td><td></td></tr>
        @foreach ($identitas as $label => $value)
            <tr><td class="b">{{ $label }}</td><td class="b colon">:</td><td class="b">{{ $value }}</td></tr>
        @endforeach
    </table>

    <h2 class="tight">SUMBER PENDAPATAN</h2>
    <table>
        <tr class="sizer"><td style="width: 76.9mm"></td><td style="width: 5mm"></td><td></td></tr>
        @foreach ($income as $row)
            <tr><td class="b">{{ $row['label'] }} (Rp)</td><td class="b colon">:</td><td class="b">Rp. {{ $row['value'] }}</td></tr>
        @endforeach
    </table>

    @foreach ($expenseGroups as $group)
        <h2 class="tight">{{ $group['title'] }}</h2>
        <table>
            <tr class="sizer"><td style="width: 8.7mm"></td><td style="width: 71.3mm"></td><td style="width: 1.1mm"></td><td style="width: 8.7mm"></td><td></td></tr>
            @foreach (array_chunk($group['rows'], 2) as $pair)
                <tr class="caption">
                    <td colspan="2">{{ $pair[0]['label'] }}</td>
                    <td></td>
                    <td colspan="2">{{ $pair[1]['label'] ?? '' }}</td>
                </tr>
                <tr>
                    @foreach ($pair as $i => $row)
                        @if ($i === 1)
                            <td></td>
                        @endif
                        <td class="b box"><span class="check">{{ $row['checked'] ? '☑' : '☐' }}</span></td>
                        <td class="b">Rp. {{ $row['value'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endforeach

    <h2 class="tight">PENDIDIKAN</h2>
    <table>
        <tr class="sizer"><td style="width: 8.7mm"></td><td></td><td style="width: 16mm"></td></tr>
        <tr class="caption"><td colspan="3">{{ $education['label'] }}</td></tr>
        <tr>
            <td class="b box"><span class="check">☐</span></td>
            <td class="b" style="border-right: none">{{ $education['value'] }}</td>
            <td class="b" style="border-left: none; text-align: right">(Orang)</td>
        </tr>
    </table>

    @foreach ($questionSections as $index => $section)
        @if ($index === 2)
            <div class="page-break"></div>
            <div class="logo"><img src="{{ $logo }}" alt=""></div>
            <h1>FORM HAD KIFAYAH</h1>
        @endif
        <h2>{{ $section['title'] }}</h2>
        <table style="{{ count($section['questions']) === 1 ? 'width: 76.6mm' : '' }}">
            @if (count($section['questions']) === 1)
                <tr class="sizer"><td></td><td style="width: 7.6mm"></td></tr>
            @else
                <tr class="sizer"><td style="width: 69.4mm"></td><td style="width: 7.6mm"></td><td style="width: 76.8mm"></td><td style="width: 7.6mm"></td></tr>
            @endif
            <tr class="caption">
                @foreach ($section['questions'] as $question)
                    <td colspan="2">{{ $question['label'] }}</td>
                @endforeach
            </tr>
            @for ($i = 0; $i < max(array_map(fn ($question) => count($question['options']), $section['questions'])); $i++)
                <tr>
                    @foreach ($section['questions'] as $question)
                        @if (isset($question['options'][$i]))
                            <td class="b">{{ $question['options'][$i]['label'] }}</td>
                            <td class="b check">{{ $question['options'][$i]['checked'] ? '✔' : '' }}</td>
                        @else
                            <td></td><td></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        </table>
        <p class="note">*Centang <span class="check">✔</span> pada kotak pilihan</p>
    @endforeach

    <p class="statement">Dengan penuh kesadaran bahwa saya bertanggungjawab penuh atas keseluruhan data dan informasi dalam isian ini dan siap bertanggungjawab penuh apabila ditemukan ketidaksesuaian data dikemudian hari.</p>

    <div class="signature">
        <p>Surabaya, {{ $signature['date'] }}</p>
        <div class="space">
            @if ($signature['image'])
                <img src="{{ $signature['image'] }}" alt="">
            @endif
        </div>
        <p>({{ $signature['name'] }})</p>
        <p>Nama Terang</p>
    </div>
</body>
</html>
