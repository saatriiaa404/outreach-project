{{-- PDF Had Kifayah, template v2: v1 plus the Baznas logo centred on top (D-064); options literal (D-037), fixed labels corrected and expense boxes ticked when the amount is not 0 (VAL-006, D-064). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 12mm 16mm 16mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8.6pt; color: #111; line-height: 1.2; }
        .logo { text-align: center; margin-bottom: 2px; }
        .logo img { height: 22mm; }
        h1 { text-align: center; font-size: 10pt; margin: 0 0 6px; }
        h2 { font-size: 8.8pt; margin: 7px 0 2px; }
        table { width: 100%; border-collapse: collapse; }
        table.grid td { border: 1px solid #111; padding: 1px 5px; vertical-align: top; }
        td.label { width: 34%; }
        td.colon { width: 8px; text-align: center; }
        td.box { width: 14px; text-align: center; }
        td.check { width: 22px; text-align: center; }
        .caption td { border: none; padding: 2px 5px 0; }
        .note { text-align: right; font-size: 7pt; margin: 1px 0 0; }
        .page-break { page-break-before: always; }
        .statement { margin-top: 18px; text-align: justify; }
        .signature { margin-top: 18px; }
        .signature p { margin: 0; }
        .signature .space { height: 70px; }
        .signature img { max-height: 70px; max-width: 220px; }
    </style>
</head>
<body>
    <div class="logo"><img src="{{ $logo }}" alt=""></div>
    <h1>FORM HAD KIFAYAH</h1>

    <h2>INFORMASI MUSTAHIK</h2>
    <table class="grid">
        @foreach ($identitas as $label => $value)
            <tr><td class="label">{{ $label }}</td><td class="colon">:</td><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    <h2>SUMBER PENDAPATAN</h2>
    <table class="grid">
        @foreach ($income as $row)
            <tr><td style="width: 58%">{{ $row['label'] }} (Rp)</td><td class="colon">:</td><td>Rp. {{ $row['value'] }}</td></tr>
        @endforeach
    </table>

    @foreach ($expenseGroups as $group)
        <h2>{{ $group['title'] }}</h2>
        <table>
            @foreach (array_chunk($group['rows'], 2) as $pair)
                <tr class="caption">
                    @foreach ($pair as $row)
                        <td colspan="2">{{ $row['label'] }}</td>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($pair as $row)
                        <td class="box" style="border: 1px solid #111">{{ $row['checked'] ? '☑' : '☐' }}</td>
                        <td style="border: 1px solid #111; width: 48%">Rp. {{ $row['value'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endforeach

    <h2>PENDIDIKAN</h2>
    <table>
        <tr class="caption"><td colspan="3">{{ $education['label'] }}</td></tr>
        <tr>
            <td class="box" style="border: 1px solid #111">☐</td>
            <td style="border: 1px solid #111; border-right: none">{{ $education['value'] }}</td>
            <td style="border: 1px solid #111; border-left: none; text-align: right; width: 60px">(Orang)</td>
        </tr>
    </table>

    @foreach ($questionSections as $index => $section)
        @if ($index === 2)
            <div class="page-break"></div>
            <h1>FORM HAD KIFAYAH</h1>
        @endif
        <h2>{{ $section['title'] }}</h2>
        <table style="{{ count($section['questions']) === 1 ? 'width: 50%' : '' }}">
            <tr class="caption">
                @foreach ($section['questions'] as $question)
                    <td colspan="2">{{ $question['label'] }}</td>
                @endforeach
            </tr>
            @for ($i = 0; $i < max(array_map(fn ($question) => count($question['options']), $section['questions'])); $i++)
                <tr>
                    @foreach ($section['questions'] as $question)
                        @if (isset($question['options'][$i]))
                            <td style="border: 1px solid #111">{{ $question['options'][$i]['label'] }}</td>
                            <td class="check" style="border: 1px solid #111">{{ $question['options'][$i]['checked'] ? '✔' : '' }}</td>
                        @else
                            <td></td><td></td>
                        @endif
                    @endforeach
                </tr>
            @endfor
        </table>
        <p class="note">*Centang ✔ pada kotak pilihan</p>
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
