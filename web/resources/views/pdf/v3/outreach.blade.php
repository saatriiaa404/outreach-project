{{-- PDF Outreach, template v3 (D-070): the Kelurahan letterhead image, "Jenis Outreach" left-aligned under the title, and page geometry measured from docs/reference/blank_outreach.pdf (A4; table 166 mm wide from 23.6 mm, rows 5 mm, Expenses on page 2). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: a4 portrait; margin: 25mm 20mm 20mm 23.6mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; color: #000; line-height: 1.25; }
        /* The rule of the letterhead image lies 52.2 mm from the top, from 19.4 mm to 194 mm. */
        .kop { margin: -11.4mm 0 0 -13.7mm; height: 42.2mm; }
        .kop img { width: 184.4mm; height: 42.2mm; }
        h1 { text-align: center; font-size: 12pt; font-weight: bold; text-decoration: underline; margin: 0.6mm 0 0; }
        .type { margin: 4mm 0 0 2mm; }
        .opening { margin: 5mm 0 0 2mm; }
        h2 { font-size: 10pt; font-weight: normal; margin: 5mm 0 1.5mm 9.6mm; }
        h2.next-page { page-break-before: always; margin-top: 0; }
        table.rows { width: 166.2mm; border-collapse: collapse; }
        table.rows td { border: 0.75pt solid #000; padding: 0.35mm 1.2mm; vertical-align: top; }
        /* A zero-height first row fixes the column widths of the source form. */
        table.rows tr.sizer td { height: 0; padding: 0; border: none; line-height: 0; font-size: 0; }
        td.colon { text-align: center; padding-left: 0; padding-right: 0; }
        tr.total td { font-weight: bold; }
        .narrative { margin: 9mm 0 0 9.6mm; }
        .narrative p { margin: 1mm 0 0; white-space: pre-line; }
        .approval { width: 64mm; margin: 14mm 0 0 auto; text-align: center; page-break-inside: avoid; }
        .approval p { margin: 0; }
        .approval .space { height: 22mm; position: relative; }
        .approval img { max-height: 22mm; max-width: 58mm; }
        .approval .stamp { position: absolute; left: -3mm; top: -4mm; max-height: 28mm; max-width: 28mm; opacity: 0.85; }
        .approval .name { font-weight: bold; text-decoration: underline; }
        .attachment { page-break-before: always; }
        .attachment h3 { font-size: 11pt; margin: 0 0 4mm; }
        .attachment img { max-width: 100%; max-height: 225mm; }
    </style>
</head>
<body>
    <div class="kop"><img src="{{ $kop }}" alt=""></div>

    <h1>{{ $document['title'] }}</h1>
    <p class="type">Jenis Outreach : {{ $document['type_label'] }}</p>

    <p class="opening">{{ $document['opening'] }}</p>

    @foreach ($document['sections'] as $index => $section)
        <h2 class="{{ $index === 2 ? 'next-page' : '' }}">{{ $section['title'] }}</h2>
        <table class="rows">
            <tr class="sizer"><td style="width: 7.8mm"></td><td style="width: 62.5mm"></td><td style="width: 5.7mm"></td><td style="width: 90.2mm"></td></tr>
            @foreach ($section['rows'] as $row)
                <tr class="{{ ($row['total'] ?? false) ? 'total' : '' }}">
                    <td class="no">{{ $row['no'] }}</td>
                    <td class="label">{{ $row['label'] }}</td>
                    <td class="colon">:</td>
                    <td>{{ $row['value'] }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    @foreach ($document['narratives'] as $narrative)
        <div class="narrative">
            {{ $narrative['title'] }} :
            <p>{{ $narrative['value'] }}</p>
        </div>
    @endforeach

    {{-- Approval block (outreach.md 8.4, D-065): filled at approval. --}}
    <div class="approval">
        <p>Surabaya, {{ $approval['date'] ?? '' }}</p>
        <p>Mengetahui,</p>
        <p>Lurah Manukan Kulon</p>
        <div class="space">
            @if (! empty($approval['signature']))
                <img src="{{ $approval['signature'] }}" alt="">
            @endif
            @if (! empty($approval['stamp']))
                <img class="stamp" src="{{ $approval['stamp'] }}" alt="">
            @endif
        </div>
        <p class="name">{{ mb_strtoupper($approval['name'] ?? '') }}</p>
        @if (! empty($approval['pangkat']))
            <p>{{ $approval['pangkat'] }}</p>
        @endif
        <p>NIP. {{ $approval['nip'] ?? '' }}</p>
    </div>

    {{-- Attachments on their own pages; PDF attachments as a reference page (D-034). --}}
    @foreach ($document['attachments'] as $attachment)
        <div class="attachment">
            <h3>Lampiran: {{ $attachment['label'] }}</h3>
            @if ($attachment['is_image'])
                <img src="{{ $attachment['url'] }}" alt="">
            @else
                <p>Lampiran berupa berkas PDF dengan nama <strong>{{ $attachment['name'] }}</strong>.</p>
                <p>Berkas tersimpan sebagai lampiran Outreach dan tidak dicetak ulang di dokumen ini.</p>
            @endif
        </div>
    @endforeach
</body>
</html>
