{{-- PDF Outreach, template v2: v1 plus the Pemkot logo beside the letterhead, the rank under the name in capitals and the stamp over the signature (D-064, D-065). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18mm 18mm 20mm 18mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #111; line-height: 1.3; }
        .letterhead { width: 100%; border-collapse: collapse; border-bottom: 2px solid #111; }
        .letterhead td { vertical-align: middle; padding: 0 0 6px; }
        .letterhead td.lh-logo { width: 22mm; }
        .letterhead td.lh-logo img { height: 24mm; }
        .letterhead td.lh-text { text-align: center; }
        .letterhead p { margin: 0; }
        .lh-0 { font-size: 10pt; }
        .lh-1, .lh-2 { font-size: 12pt; font-weight: bold; }
        .lh-small { font-size: 8pt; }
        h1 { text-align: center; font-size: 11.5pt; text-decoration: underline; margin: 14px 0 0; }
        .type { text-align: center; font-weight: bold; margin: 2px 0 12px; }
        h2 { font-size: 9.5pt; font-weight: normal; margin: 12px 0 3px 28px; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows td { border: 1px solid #111; padding: 2px 4px; vertical-align: top; }
        td.no { width: 18px; }
        td.label { width: 38%; }
        td.colon { width: 6px; text-align: center; }
        tr.total td { font-weight: bold; }
        .narrative { margin: 14px 0 0 28px; }
        .narrative p { margin: 3px 0 0; white-space: pre-line; }
        .approval { width: 240px; margin: 28px 0 0 auto; text-align: center; page-break-inside: avoid; }
        .approval p { margin: 0; }
        .approval .space { height: 70px; position: relative; }
        .approval img { max-height: 70px; max-width: 200px; }
        .approval .stamp { position: absolute; left: -10px; top: -12px; max-height: 90px; max-width: 90px; opacity: 0.85; }
        .approval .name { font-weight: bold; text-decoration: underline; }
        .attachment { page-break-before: always; }
        .attachment h3 { font-size: 10.5pt; margin: 0 0 10px; }
        .attachment img { max-width: 100%; max-height: 225mm; }
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            <td class="lh-logo"><img src="{{ $logo }}" alt=""></td>
            <td class="lh-text">
                @foreach ($document['letterhead'] as $i => $line)
                    <p class="{{ $i === 0 ? 'lh-0' : ($i < 3 ? 'lh-'.$i : 'lh-small') }}">{{ $line }}</p>
                @endforeach
            </td>
            <td class="lh-logo"></td>
        </tr>
    </table>

    <h1>{{ $document['title'] }}</h1>
    <p class="type">{{ $document['type'] }}</p>

    <p>{{ $document['opening'] }}</p>

    @foreach ($document['sections'] as $section)
        <h2>{{ $section['title'] }}</h2>
        <table class="rows">
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

    {{-- Approval block (outreach.md 8.4, VAL-004): centred, filled at approval. --}}
    <div class="approval">
        <p>Surabaya, {{ $approval['date'] ?? '' }}</p>
        <p>Mengetahui,</p>
        <p>Lurah Manukan Kulon</p>
        <div class="space">
            @if (! empty($approval['signature']))
                <img src="{{ $approval['signature'] }}" alt="">
            @endif
            {{-- The stamp overlaps the left of the signature (D-065). --}}
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
