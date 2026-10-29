{{-- PDF Mustahik, template v2: v1 plus the Baznas logo centred on top (D-064); labels literal (D-022); the score column only holds a check (D-053). --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 9mm 9mm 9mm 9mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 6.3pt; color: #111; line-height: 1.12; }
        .logo { text-align: center; margin-bottom: 2px; }
        .logo img { height: 20mm; }
        .heading { text-align: center; font-weight: bold; font-size: 8pt; margin-bottom: 6px; }
        .heading p { margin: 0; }
        table.header { border-collapse: collapse; margin-bottom: 6px; font-size: 7pt; }
        table.header td { padding: 0 4px 0 0; vertical-align: top; }
        td.h-label { width: 140px; }
        td.h-colon { width: 8px; }
        .bold { font-weight: bold; }
        table.columns { width: 100%; border-collapse: collapse; }
        table.columns > tbody > tr > td { width: 33.3%; vertical-align: top; padding: 0 3px; }
        table.q { width: 100%; border-collapse: collapse; }
        table.q td { border: 0.6px solid #111; padding: 0 2px; vertical-align: top; }
        td.q-no { width: 10px; text-align: center; vertical-align: middle; }
        td.q-letter { width: 9px; }
        td.q-score { width: 22px; text-align: center; }
        tr.q-title td.q-text { font-weight: bold; }
        .statement { border: 1px solid #111; padding: 10px 6px; text-align: center; font-weight: bold; margin-top: 12px; }
        .signature { text-align: center; margin-top: 18px; }
        .signature p { margin: 0; }
        .signature .space { height: 55px; }
        .signature img { max-height: 55px; max-width: 170px; }
    </style>
</head>
<body>
    <div class="logo"><img src="{{ $logo }}" alt=""></div>
    <div class="heading">
        <p>FORM SURVEY MUSTAHIK</p>
        <p>BADAN AMIL ZAKAT NASIONAL KOTA SURABAYA</p>
    </div>

    <table class="header">
        <tr><td class="h-label">Nama Petugas</td><td class="h-colon">:</td><td>{{ $header['petugas'] }}</td></tr>
        <tr><td class="h-label">Tanggal /Hari (Survey)</td><td class="h-colon">:</td><td>{{ $header['survey'] }}</td></tr>
        <tr><td class="h-label">Kelurahan</td><td class="h-colon">:</td><td>{{ $header['kelurahan'] }}</td></tr>
        <tr><td class="h-label">Kecamatan</td><td class="h-colon">:</td><td>{{ $header['kecamatan'] }}</td></tr>
        <tr><td class="h-label bold" colspan="3">DATA KEPALA KELUARGA</td></tr>
        <tr><td class="h-label">Nama</td><td class="h-colon">:</td><td>{{ $header['kk_nama'] }}</td></tr>
        <tr><td class="h-label">NIK / No KK</td><td class="h-colon">:</td><td>{{ $header['kk_nik'] }}</td></tr>
        <tr>
            <td class="h-label">Alamat</td><td class="h-colon">:</td>
            <td>Jalan {{ $header['kk_alamat'] }} &nbsp; Kelurahan {{ $header['kelurahan'] }}<br>
                Kecamatan {{ $header['kecamatan'] }} &nbsp; RT {{ $header['kk_rt'] }} &nbsp; RW. {{ $header['kk_rw'] }}</td>
        </tr>
        <tr><td class="h-label">Nomor Telepon HP</td><td class="h-colon">:</td><td>{{ $header['no_hp'] }}</td></tr>
    </table>

    <table class="columns">
        <tr>
            @foreach ($sections as $index => $section)
                <td>
                    <table class="q">
                        <tr class="q-title">
                            <td class="q-text" colspan="3">{{ $section['title'] }}</td>
                            <td class="q-score bold">{{ $index === 0 ? '' : 'score' }}</td>
                        </tr>
                        @foreach ($section['questions'] as $question)
                            <tr class="q-title">
                                <td class="q-no" rowspan="{{ count($question['options']) + 1 }}">{{ $question['no'] }}</td>
                                <td class="q-text" colspan="2">{{ $question['label'] }}</td>
                                <td class="q-score"></td>
                            </tr>
                            @foreach ($question['options'] as $option)
                                <tr>
                                    <td class="q-letter">{{ $option['letter'] }}.</td>
                                    <td>{{ $option['label'] }}</td>
                                    <td class="q-score">{{ $option['checked'] ? '✓' : '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </table>

                    @if ($index === 2)
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
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
</body>
</html>
