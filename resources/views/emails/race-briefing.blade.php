@php
  $reg = $registration;
  $cat = $reg->category;
  $setting = \App\Models\GtrSetting::first();
  $target = $reg->target_finish_minutes ?: \App\Http\Controllers\RunnerDashboardController::defaultTarget($cat);
  $plan = $target && $cat ? $cat->racePlan($target) : [];
  $weather = app(\App\Services\RaceWeather::class)->forRace();
  $hm = fn ($m) => intdiv((int) $m, 60) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
  $clock = fn ($m) => $m === null ? '-' : str_pad((string) (intdiv((int) $m, 60) % 24), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
  $appUrl = route('gtr.login');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Briefing Race Day</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <tr>
            <td style="background:#101010;padding:28px 32px;color:#ffffff;border-bottom:4px solid #E53935">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.75;margin-bottom:6px">{{ $setting->title ?? 'Gerung Trail Run 2026' }} · H-1</div>
              <div style="font-size:22px;font-weight:800">Besok Race Day! 🏁</div>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">Halo <strong>{{ $reg->full_name }}</strong>,</p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">Ini briefing singkat untuk besok. Simpan email ini ya.</p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 20px">
                <tr><td style="padding:10px 16px;color:#667;width:40%">Kategori · BIB</td><td style="padding:10px 16px;font-weight:800">{{ $cat->distance ?? '' }} · {{ $reg->bib_number ?: '-' }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Tanggal</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $setting->date_text ?? 'Minggu, 29 November 2026' }}</td></tr>
                @if($setting?->assembly_time)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Jam Kumpul</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $setting->assembly_time }}</td></tr>
                @endif
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Start</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $cat->start_time ?: '-' }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Cut-Off</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $cat->cut_off_time ?: '-' }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Lokasi</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $setting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat' }}</td></tr>
              </table>

              @if($weather)
                @php [$wxLabel, $wxIcon] = \App\Services\RaceWeather::describe($weather['code']); @endphp
                <div style="margin:0 0 20px;padding:14px 16px;border-radius:12px;background:#f0f9ff;border:1px solid #bae6fd">
                  <div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#0369a1;font-weight:700">Cuaca race day</div>
                  <div style="font-size:18px;font-weight:800;margin-top:4px">{{ $wxIcon }} {{ $weather['temp_min'] }}–{{ $weather['temp_max'] }}°C · {{ $wxLabel }}</div>
                  <div style="font-size:13px;color:#334155;margin-top:4px">{{ $weather['mode'] === 'forecast' ? 'Peluang hujan' : 'Hari hujan' }} {{ $weather['rain'] }}% · angin {{ $weather['wind'] }} km/j · matahari terbit {{ $weather['sunrise'] ?? '-' }}</div>
                  @foreach($weather['tips'] as $tip)<div style="font-size:13px;color:#334155;margin-top:6px">🎒 {{ $tip }}</div>@endforeach
                </div>
              @endif

              @if(count($plan))
                <div style="font-size:15px;font-weight:800;margin:0 0 8px">Race plan · target {{ $hm($target) }}{{ $reg->target_finish_minutes ? '' : ' (estimasi bawaan)' }}</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:13px;margin:0 0 20px">
                  <tr style="background:#f8fafc;color:#667;font-size:11px;text-transform:uppercase;letter-spacing:1px">
                    <td style="padding:8px 14px">Pos</td><td style="padding:8px 14px">KM</td><td align="right" style="padding:8px 14px">Perkiraan</td><td align="right" style="padding:8px 14px">COT</td>
                  </tr>
                  @foreach($plan as $row)
                  <tr>
                    <td style="padding:8px 14px;border-top:1px solid #eef;font-weight:700">{{ $row['code'] }}</td>
                    <td style="padding:8px 14px;border-top:1px solid #eef">{{ rtrim(rtrim(number_format($row['km'], 1), '0'), '.') }}</td>
                    <td align="right" style="padding:8px 14px;border-top:1px solid #eef;font-weight:800">{{ $clock($row['eta']) }}</td>
                    <td align="right" style="padding:8px 14px;border-top:1px solid #eef;color:#b91c1c">{{ $row['cutoff'] !== null ? $clock($row['cutoff']) : '' }}</td>
                  </tr>
                  @endforeach
                </table>
              @endif

              @if(!empty($cat->mandatory_gear))
                <div style="font-size:15px;font-weight:800;margin:0 0 8px">Checklist mandatory gear</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:0 0 20px">
                  @foreach($cat->mandatory_gear as $item)
                    <tr><td style="padding:5px 0;color:#334155">☐ {{ $item }}</td></tr>
                  @endforeach
                </table>
              @endif

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $appUrl }}" style="display:inline-block;background:#E53935;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px">Buka Race Plan & Pace Band →</a>
                    @if($reg->bib_number)
                      <div style="margin-top:12px;font-size:13px;color:#555">Link lacak untuk keluarga: <a href="{{ route('gtr.track', $reg->bib_number) }}" style="color:#E53935">{{ route('gtr.track', $reg->bib_number) }}</a></div>
                    @endif
                  </td>
                </tr>
              </table>

              <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#666">
                Tips: buka Runger App → detail kategori → <strong>Simpan offline</strong> malam ini, karena sinyal di bukit bisa lemah.<br><br>
                Istirahat cukup, sampai jumpa di garis start! 🏃<br>Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#101010;padding:18px 32px;color:#9a9a9a;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis ke peserta terdaftar Gerung Trail Run 2026.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
