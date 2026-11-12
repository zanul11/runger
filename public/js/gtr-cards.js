/*
 * GTR finisher cards — e-certificate (A4 landscape) and Instagram story (9:16).
 *
 * Digambar langsung ke <canvas> (bukan HTML/SVG) supaya yang tampil = yang diunduh, di semua
 * browser termasuk Safari iOS. Koordinat mengikuti desain "E-Certificate & Story Card GTR":
 * sertifikat 1123×794, story 1080×1920 (px desain).
 *
 * Pemakaian: halaman mengisi window.GTR_CARD lalu memuat file ini. Tiap
 * <canvas data-card="cert|story"> digambar; tombol [data-dl="cert|story"] mengunduh PNG.
 */
(function () {
  'use strict';

  var BC = '"Barlow Condensed","Arial Narrow",sans-serif';
  var BL = 'Barlow,system-ui,sans-serif';
  var MONO = 'ui-monospace,Menlo,Consolas,monospace';

  var THEMES = {
    red: { ink: '#1c1414', deep: '#7d1219', label: '#9e1922', main: '#b81d27', bright: '#c8202b', soft: '#ecc9c5', g0: '#9a1620', g1: '#3d070b', flip: false },
    green: { ink: '#141c17', deep: '#24452f', label: '#2f5a43', main: '#3f7356', bright: '#4a8062', soft: '#c4d9cb', g0: '#2f6044', g1: '#0e2217', flip: true }
  };

  // Ukuran & posisi (baseline teks) kartu story.
  var FORMATS = {
    story: {
      W: 1080, H: 1920, ghost: [520, 988], gtr: [90, 130, 230, 90], div: [344, 144, 62], rg: [370, 147, 150, 56],
      badge: { y: 136.5, h: 77, f1: 60, p1: 14, b1: 202.5, f2: 40, p2: 16, b2: 193.5 },
      eyebrow: { f: 26, b: 315, ly: 304 }, fin: { f: 224, b: 519 },
      chip: { y: 554.2, h: 59, f: 46, b: 604.2 }, name: { f: 64, b: 608.7 },
      ftl: { f: 24, b: 692.7 }, time: { f: 230, b: 897.7 },
      grid: { y: 949.5, h: 131.6, px: 25, lf: 20, lb: 990.5, vf: 64, vb: 1056.5, cf: 44, cb: 1039.5 },
      route: { y: 1126, h: 400, lf: 20 },
      date: { f: 28, b: 1626.2, lf: 24, lb: 1658.6 }, tag: { f: 40, b: 1631.2, sf: 22, sb: 1659.2 },
      sp: { y: 1696, h: 94, lh: 62, gap: 44, max: 190 }
    }
  };

  /* ---------- helper gambar ---------- */

  var HAS_LS = typeof CanvasRenderingContext2D !== 'undefined' && 'letterSpacing' in CanvasRenderingContext2D.prototype;

  function fnt(style, weight, size, family) {
    return (style === 'i' ? 'italic ' : '') + weight + ' ' + size + 'px ' + family;
  }

  /** Lebar teks termasuk letter-spacing (ls, px) setelah tiap karakter — sama seperti CSS. */
  function tw(c, s, font, ls) {
    c.font = font;
    if (!ls) return c.measureText(s).width;
    if (HAS_LS) {
      c.letterSpacing = ls + 'px';
      var w = c.measureText(s).width;
      c.letterSpacing = '0px';
      return w;
    }
    return c.measureText(s).width + ls * Array.from(s).length;
  }

  /** Tulis teks pada baseline y. o: {ls, align: left|center|right, alpha}. Mengembalikan lebarnya. */
  function text(c, s, x, y, font, color, o) {
    o = o || {};
    var ls = o.ls || 0, w = tw(c, s, font, ls);
    if (o.align === 'right') x -= w; else if (o.align === 'center') x -= w / 2;
    c.save();
    c.font = font; c.fillStyle = color; c.textAlign = 'left'; c.textBaseline = 'alphabetic';
    if (o.alpha != null) c.globalAlpha = o.alpha;
    if (!ls) {
      c.fillText(s, x, y);
    } else if (HAS_LS) {
      c.letterSpacing = ls + 'px';
      c.fillText(s, x, y);
    } else {
      Array.from(s).forEach(function (ch) { c.fillText(ch, x, y); x += c.measureText(ch).width + ls; });
    }
    c.restore();
    return w;
  }

  /** Teks satu baris yang dikecilkan sampai muat maxW; kalau masih kepanjangan dipotong "…". */
  function fitText(c, s, x, y, style, weight, size, family, color, maxW, o) {
    o = o || {};
    var em = o.em || 0, min = size * (o.min || 0.6), f = size;
    while (f > min && tw(c, s, fnt(style, weight, f, family), em * f) > maxW) f -= 1;
    var font = fnt(style, weight, f, family);
    if (tw(c, s, font, em * f) > maxW) {
      var chars = Array.from(s);
      while (chars.length > 1 && tw(c, chars.join('').trim() + '…', font, em * f) > maxW) chars.pop();
      s = chars.join('').trim() + '…';
    }
    return { w: text(c, s, x, y, font, color, { ls: em * f, align: o.align, alpha: o.alpha }), f: f };
  }

  function rr(c, x, y, w, h, r) {
    var a = typeof r === 'number' ? [r, r, r, r] : r; // tl, tr, br, bl
    c.beginPath();
    c.moveTo(x + a[0], y);
    c.lineTo(x + w - a[1], y); c.arcTo(x + w, y, x + w, y + a[1], a[1]);
    c.lineTo(x + w, y + h - a[2]); c.arcTo(x + w, y + h, x + w - a[2], y + h, a[2]);
    c.lineTo(x + a[3], y + h); c.arcTo(x, y + h, x, y + h - a[3], a[3]);
    c.lineTo(x, y + a[0]); c.arcTo(x, y, x + a[0], y, a[0]);
    c.closePath();
  }

  /** Bayangan ala CSS box-shadow (dengan spread negatif) di belakang kotak membulat. k = skala kanvas. */
  function shadowBox(c, x, y, w, h, r, oy, blur, spread, color, k) {
    c.save();
    c.shadowColor = color; c.shadowBlur = blur * k; c.shadowOffsetY = oy * k;
    c.fillStyle = color;
    rr(c, x - spread, y - spread, w + spread * 2, h + spread * 2, Math.max(0, r + spread));
    c.fill();
    c.restore();
  }

  /** object-fit: contain di dalam kotak (x,y,w,h). */
  function contain(c, img, x, y, w, h, alignLeft) {
    if (!img) return;
    var iw = img.naturalWidth || img.width, ih = img.naturalHeight || img.height;
    if (!iw || !ih) return;
    var s = Math.min(w / iw, h / ih), dw = iw * s, dh = ih * s;
    c.drawImage(img, alignLeft ? x : x + (w - dw) / 2, y + (h - dh) / 2, dw, dh);
  }

  /** Salinan gambar yang diwarnai polos (pengganti CSS filter: brightness(0) invert(1)). */
  function tinted(img, color) {
    if (!img) return null;
    var cv = document.createElement('canvas');
    var s = Math.min(1, 800 / (img.naturalWidth || 1));
    cv.width = Math.max(1, Math.round(img.naturalWidth * s));
    cv.height = Math.max(1, Math.round(img.naturalHeight * s));
    var x = cv.getContext('2d');
    x.drawImage(img, 0, 0, cv.width, cv.height);
    x.globalCompositeOperation = 'source-in';
    x.fillStyle = color;
    x.fillRect(0, 0, cv.width, cv.height);
    return cv;
  }

  /**
   * Sponsor di pita putih membulat — sama seperti kiosk Cek BIB / Cek Hasil: logo setinggi lh
   * (logo lebar 90%-nya), lebar maksimum max. w null = pita selebar isinya, rata tengah di cx.
   */
  function sponsorPill(c, items, cx, y, h, o, k) {
    var list = items.filter(function (i) { return i.im; }).map(function (i) {
      var ih = i.wide ? o.lh * 0.9 : o.lh;
      return { im: i.im, h: ih, w: Math.min(o.max, ih * i.im.naturalWidth / i.im.naturalHeight) };
    });
    var inner = list.reduce(function (a, i) { return a + i.w; }, 0) + o.gap * Math.max(0, list.length - 1);
    var w = o.w || inner + o.pad * 2, x = cx - w / 2;
    shadowBox(c, x, y, w, h, o.r, o.sy, o.sb, o.ss, o.sc, k);
    c.fillStyle = '#fff'; rr(c, x, y, w, h, o.r); c.fill();
    var lx = cx - inner / 2;
    list.forEach(function (i) { contain(c, i.im, lx, y + (h - i.h) / 2, i.w, i.h); lx += i.w + o.gap; });
  }

  /* ---------- latar: punggung bukit dari profil elevasi + garis kontur ---------- */

  function ridge(ele, W, YB, h, shift, flip) {
    var e = flip ? ele.slice().reverse() : ele, n = e.length - 1, mx = Math.max.apply(null, e) || 1;
    var d = 'M' + e.map(function (v, i) {
      return ((i / n) * (W + 40) - 20 + shift).toFixed(1) + ',' + (YB - (v / mx) * h).toFixed(1);
    }).join('L');
    return d + 'L' + (W + 20 + shift).toFixed(1) + ',' + YB + 'L' + (-20 + shift).toFixed(1) + ',' + YB + 'Z';
  }

  function topo(cx, cy, seed, rings, step, sx) {
    var d = '';
    for (var k = 1; k <= rings; k++) {
      var r = k * step, s = '';
      for (var a = 0; a <= 72; a++) {
        var t = (a / 72) * Math.PI * 2;
        var q = r * (1 + 0.16 * Math.sin(3 * t + seed + k * 0.12) + 0.07 * Math.sin(5 * t - seed * 1.7) + 0.04 * Math.cos(7 * t + k * 0.3));
        s += (a ? 'L' : 'M') + (cx + q * Math.cos(t) * sx).toFixed(1) + ',' + (cy + q * Math.sin(t)).toFixed(1);
      }
      d += s + 'Z';
    }
    return d;
  }

  function fillPath(c, d, color, alpha) {
    c.save(); c.globalAlpha = alpha; c.fillStyle = color; c.fill(new Path2D(d)); c.restore();
  }

  function strokePath(c, d, color, alpha, width) {
    c.save(); c.globalAlpha = alpha; c.strokeStyle = color; c.lineWidth = width; c.stroke(new Path2D(d)); c.restore();
  }

  /* ---------- 1. E-certificate · 1123 × 794 ---------- */

  function drawCert(c, d, t, im, k) {
    var W = 1123, H = 794, L = 56, R = 1067, grey = '#5a5050';
    c.fillStyle = '#fbfaf8'; c.fillRect(0, 0, W, H);

    strokePath(c, topo(t.flip ? 160 : 960, 150, t.flip ? 2.1 : 0.7, 12, 24, 1.35), t.main, 0.11, 1.2);
    if (d.ele.length > 1) {
      fillPath(c, ridge(d.ele, W, 716, 150, -60, !t.flip), t.main, 0.06);
      fillPath(c, ridge(d.ele, W, 716, 100, 30, true), t.main, 0.12);
      fillPath(c, ridge(d.ele, W, 716, 46, 0, false), t.bright, 1);
    }

    // bingkai tipis (tanpa sisi bawah)
    c.strokeStyle = t.soft; c.lineWidth = 1.5;
    c.beginPath(); c.moveTo(18.75, 698); c.lineTo(18.75, 18.75); c.lineTo(1104.25, 18.75); c.lineTo(1104.25, 698); c.stroke();

    text(c, d.cat, 1083, 390, fnt('i', 900, 300, BC), t.main, { align: 'right', alpha: 0.06 });

    // logo GTR + lencana Runger (biru)
    contain(c, im.gtr, L, 38, 150, 58);
    shadowBox(c, 220, 47, 104, 40, 10, 4, 10, -4, 'rgba(0,0,0,.25)', k);
    c.fillStyle = '#0540a7'; rr(c, 220, 47, 104, 40, 10); c.fill();
    contain(c, im.rungerBadge, 232, 53, 80, 28);

    // label kategori kanan atas
    var f1 = fnt('i', 900, 40, BC), f2 = fnt('i', 900, 31, BC);
    var w1 = tw(c, d.cat, f1) + 24, w2 = d.tag ? tw(c, d.tag, f2, 0.31) + 28 : 0, bx = R - w1 - w2;
    c.save();
    rr(c, bx, 18, w1 + w2, 56, [0, 0, 4, 4]); c.clip();
    c.fillStyle = t.main; c.fillRect(bx, 18, w1, 56);
    c.fillStyle = t.deep; c.fillRect(bx + w1, 18, w2, 56);
    c.restore();
    text(c, d.cat, bx + 12, 66, f1, '#fff');
    if (d.tag) text(c, d.tag, bx + w1 + 14, 60.5, f2, '#fff', { ls: 0.31 });

    // judul
    c.fillStyle = t.main; c.fillRect(L, 132.8, 26, 1.5);
    text(c, 'E-CERTIFICATE · GERUNG TRAIL RUN 2026', 92, 139, fnt('n', 700, 15, BC), t.label, { ls: 4.8 });
    text(c, 'CERTIFICATE OF', L, 226, fnt('i', 900, 86, BC), t.ink, { ls: -0.43 });
    text(c, 'FINISHER', L, 303.4, fnt('i', 900, 86, BC), t.main, { ls: -0.43 });

    // nama + garis bawah selebar nama
    text(c, 'This certificate is proudly presented to', L, 343.8, fnt('n', 500, 16, BL), grey);
    var nm = fitText(c, d.name.toUpperCase(), L, 400.6, 'n', 800, 54, BC, t.ink, 900, { em: 0.03, min: 0.5 });
    c.fillStyle = t.main; c.fillRect(L, 416.6, nm.w, 3);

    richText(c, [
      { t: 'for successfully completing the ' },
      { t: d.cat + (d.tagTitle ? ' ' + d.tagTitle : ''), b: true },
      { t: ' category at ' },
      { t: 'Gerung Trail Run 2026 · Keteri Hill', b: true },
      { t: ', held on ' + d.date + ' at Keteri Hill, Gerung, West Lombok, Indonesia.' }
    ], L, 451.1, 760, 15.5, 23.25, '#3d3434', t.ink);

    // kotak statistik
    var sy = 506.1, sh = 82.4;
    c.save();
    rr(c, L, sy, 1011, sh, 4); c.clip();
    c.fillStyle = 'rgba(255,255,255,.88)'; c.fillRect(L, sy, 1011, sh);
    c.fillStyle = t.main; c.fillRect(L, sy, 5, sh);
    c.restore();
    c.strokeStyle = t.soft; c.lineWidth = 1.5;
    c.beginPath();
    c.moveTo(61, sy + 0.75); c.lineTo(R - 4, sy + 0.75); c.arcTo(R - 0.75, sy + 0.75, R - 0.75, sy + 4, 3.25);
    c.lineTo(R - 0.75, sy + sh - 4); c.arcTo(R - 0.75, sy + sh - 0.75, R - 4, sy + sh - 0.75, 3.25); c.lineTo(61, sy + sh - 0.75);
    c.stroke();
    var cells = [
      { x: 61, w: 177.3, l: 'BIB NUMBER', v: d.bib, f: 34, b: 564.1, col: t.ink },
      { x: 238.3, w: 247.5, l: 'FINISH TIME', v: d.time, f: 46, b: 575.1, col: t.main },
      { x: 485.9, w: 226.5, l: 'CATEGORY', v: d.catGender, f: 30, b: 561.1, col: t.ink },
      { x: 712.3, w: 177.3, l: 'RANK', v: '#' + d.rank, f: 34, b: 564.1, col: t.ink },
      { x: 889.7, w: 176.3, l: 'ELEVATION GAIN', v: d.gain ? '+' + d.gain + ' M' : '—', f: 34, b: 564.1, col: t.ink }
    ];
    cells.forEach(function (s, i) {
      if (i) { c.fillStyle = t.soft; c.fillRect(s.x - 1.5, sy + 1.5, 1.5, sh - 3); }
      text(c, s.l, s.x + 18, 529.1, fnt('n', 700, 11, BL), t.label, { ls: 1.76 });
      fitText(c, s.v, s.x + 18, s.b, 'i', 900, s.f, BC, s.col, s.w - 30);
    });

    // nomor sertifikat + penerbit
    text(c, 'CERTIFICATE NO.', L, 619, fnt('n', 600, 11, BL), grey, { ls: 0.88 });
    text(c, d.certNo, L, 636.4, fnt('n', 700, 14, MONO), t.ink, { ls: 0.28 });
    // penerbit, rata kanan sejajar nomor sertifikat (ls ikut dikurangi supaya tepi kanan pas di R)
    text(c, 'ISSUED BY', R + 0.88, 619, fnt('n', 600, 11, BL), grey, { ls: 0.88, align: 'right' });
    text(c, 'RUNNERS GERUNG', R + 0.68, 637, fnt('n', 800, 17, BC), t.ink, { ls: 0.68, align: 'right' });

    // pita bawah + sponsor
    c.save();
    c.beginPath(); c.rect(0, 716, W, 78); c.clip();
    var g = c.createLinearGradient(0, 716, 0, 716 + 78 * 1.3);
    g.addColorStop(0, t.bright); g.addColorStop(1, t.deep);
    c.fillStyle = g; c.fillRect(0, 716, W, 78);
    text(c, d.cat, 1129, 817, fnt('i', 900, 150, BC), '#fff', { align: 'right', alpha: 0.07 });
    c.restore();
    sponsorPill(c, im.sponsors, W / 2, 726, 58, { lh: 40, gap: 28, max: 124, pad: 28, r: 14, sy: 10, sb: 22, ss: -12, sc: 'rgba(0,0,0,.55)' }, k);
  }

  /** Paragraf dengan potongan tebal, dibungkus per kata dalam maxW. */
  function richText(c, runs, x, y, maxW, size, lh, color, boldColor) {
    var words = [{ p: [], sp: false }];
    runs.forEach(function (r) {
      r.t.split(/( )/).forEach(function (s) {
        if (s === ' ') words.push({ p: [], sp: !!r.b });
        else if (s) words[words.length - 1].p.push({ s: s, b: !!r.b });
      });
    });
    var font = function (b) { return fnt('n', b ? 700 : 500, size, BL); };
    var cx = x;
    words.forEach(function (w, i) {
      if (!w.p.length) return;
      var ww = 0;
      w.p.forEach(function (p) { p.w = tw(c, p.s, font(p.b)); ww += p.w; });
      var sp = i ? tw(c, ' ', font(w.sp)) : 0;
      if (cx > x && cx + sp + ww > x + maxW) { cx = x; y += lh; sp = 0; }
      cx += sp;
      w.p.forEach(function (p) { text(c, p.s, cx, y, font(p.b), p.b ? boldColor : color); cx += p.w; });
    });
  }

  /* ---------- 2. Instagram story · 1080 × 1920 ---------- */

  function drawSocial(c, d, t, im, k, m) {
    var W = m.W, H = m.H, L = 90, R = 990, sc = H / 1920, soft = 'rgba(255,255,255,.8)';

    // linear-gradient(170deg, g0 0%, g1 78%)
    var a = 170 * Math.PI / 180, dx = Math.sin(a), dy = -Math.cos(a), len = Math.abs(W * dx) + Math.abs(H * dy);
    var g = c.createLinearGradient(W / 2 - dx * len / 2, H / 2 - dy * len / 2, W / 2 + dx * len / 2, H / 2 + dy * len / 2);
    g.addColorStop(0, t.g0); g.addColorStop(0.78, t.g1); g.addColorStop(1, t.g1);
    c.fillStyle = g; c.fillRect(0, 0, W, H);

    strokePath(c, topo(t.flip ? 160 : 900, 360 * sc, t.flip ? 2.1 : 0.7, 16, 34, 1.2) + topo(t.flip ? 960 : 120, 1180 * sc, t.flip ? 4.2 : 3.3, 13, 36, 1.2), '#fff', 0.07, 1.6);
    if (d.ele.length > 1) {
      fillPath(c, ridge(d.ele, 1080, H, 300 * sc, 40, !t.flip), '#000', 0.18);
      fillPath(c, ridge(d.ele, 1080, H, 170 * sc, 0, t.flip), '#000', 0.22);
    }
    text(c, d.cat, 1110, m.ghost[1], fnt('i', 900, m.ghost[0], BC), '#fff', { align: 'right', alpha: 0.05 });

    // logo
    contain(c, im.gtrWhite, m.gtr[0], m.gtr[1], m.gtr[2], m.gtr[3], true);
    c.fillStyle = 'rgba(255,255,255,.35)'; c.fillRect(m.div[0], m.div[1], 2, m.div[2]);
    contain(c, im.rungerWhite, m.rg[0], m.rg[1], m.rg[2], m.rg[3]);

    // label kategori
    var b = m.badge, f1 = fnt('i', 900, b.f1, BC), f2 = fnt('i', 900, b.f2, BC);
    var w1 = tw(c, d.cat, f1) + b.p1 * 2, w2 = d.tag ? tw(c, d.tag, f2) + b.p2 * 2 : 0, bx = R - w1 - w2;
    shadowBox(c, bx, b.y, w1 + w2, b.h, 6, 10, 24, -10, 'rgba(0,0,0,.5)', k);
    c.save();
    rr(c, bx, b.y, w1 + w2, b.h, 6); c.clip();
    c.fillStyle = '#fff'; c.fillRect(bx, b.y, w1, b.h);
    c.fillStyle = t.deep; c.fillRect(bx + w1, b.y, w2, b.h);
    c.restore();
    text(c, d.cat, bx + b.p1, b.b1, f1, t.main);
    if (d.tag) text(c, d.tag, bx + w1 + b.p2, b.b2, f2, '#fff');

    // judul, nomor BIB, nama
    c.fillStyle = '#fff'; c.fillRect(L, m.eyebrow.ly, 44, 2);
    text(c, 'GERUNG TRAIL RUN 2026', 150, m.eyebrow.b, fnt('n', 700, m.eyebrow.f, BC), 'rgba(255,255,255,.85)', { ls: 0.32 * m.eyebrow.f });
    text(c, 'FINISHER', L, m.fin.b, fnt('i', 900, m.fin.f, BC), '#fff', { ls: -0.01 * m.fin.f });
    var cf = fnt('i', 900, m.chip.f, BC), cw = tw(c, '#' + d.bib, cf) + 28;
    c.fillStyle = '#fff'; rr(c, L, m.chip.y, cw, m.chip.h, 6); c.fill();
    text(c, '#' + d.bib, L + 14, m.chip.b, cf, t.ink);
    fitText(c, d.name.toUpperCase(), L + cw + 20, m.name.b, 'n', 800, m.name.f, BC, '#fff', R - (L + cw + 20), { em: 0.03, min: 0.62 });

    // waktu finish
    text(c, 'FINISH TIME', L, m.ftl.b, fnt('n', 700, m.ftl.f, BL), soft, { ls: 0.2 * m.ftl.f });
    tabular(c, d.time, L, m.time.b, m.time.f, '#fff', -0.005 * m.time.f, R - L);

    // empat kotak statistik
    var gr = m.grid;
    [
      { x: 90, w: 188.6, l: 'DISTANCE', v: d.distance },
      { x: 292.6, w: 188.6, l: 'ELEV. GAIN', v: d.gain ? '+' + d.gain + 'M' : '—' },
      { x: 495.1, w: 292.3, l: 'CATEGORY', v: d.catGender, cat: true },
      { x: 801.4, w: 188.6, l: 'RANK', v: '#' + d.rank }
    ].forEach(function (s) {
      c.fillStyle = 'rgba(255,255,255,.08)'; rr(c, s.x, gr.y, s.w, gr.h, 14); c.fill();
      c.strokeStyle = 'rgba(255,255,255,.18)'; c.lineWidth = 1.5; rr(c, s.x + 0.75, gr.y + 0.75, s.w - 1.5, gr.h - 1.5, 13.25); c.stroke();
      text(c, s.l, s.x + gr.px, gr.lb, fnt('n', 700, gr.lf, BL), soft, { ls: 0.16 * gr.lf });
      fitText(c, s.v, s.x + gr.px, s.cat ? gr.cb : gr.vb, 'i', 900, s.cat ? gr.cf : gr.vf, BC, '#fff', s.w - gr.px - 14);
    });

    // peta rute
    if ((d.route || []).length > 1) routeMap(c, d, t, m.route, L, R - L);

    // tanggal, lokasi, tagar
    text(c, d.date.toUpperCase(), L, m.date.b, fnt('n', 700, m.date.f, BC), '#fff', { ls: 0.06 * m.date.f });
    text(c, 'Keteri Hill · Gerung · West Lombok', L, m.date.lb, fnt('n', 500, m.date.lf, BL), soft, { ls: 0.02 * m.date.lf });
    text(c, '#GTR2026', R, m.tag.b, fnt('i', 900, m.tag.f, BC), '#fff', { align: 'right' });
    text(c, '#GerungTrailRun', R, m.tag.sb, fnt('n', 700, m.tag.sf, BL), soft, { ls: 0.06 * m.tag.sf, align: 'right' });

    // panel sponsor
    var sp = m.sp;
    sponsorPill(c, im.sponsors, W / 2, sp.y, sp.h, { w: 900, lh: sp.lh, gap: sp.gap, max: sp.max, r: 16, sy: 16, sb: 36, ss: -18, sc: 'rgba(0,0,0,.6)' }, k);
  }

  /** Waktu dengan angka selebar sama (tabular), supaya 01:11:11 dan 02:48:08 sama panjang. */
  function tabular(c, s, x, y, size, color, ls, maxW) {
    var font = fnt('i', 900, size, BC);
    c.font = font;
    var dw = size * 0.524; // lebar angka tabular (tnum) Barlow Condensed Black Italic
    '0123456789'.split('').forEach(function (n) { dw = Math.max(dw, c.measureText(n).width); });
    var chars = Array.from(s), widths = chars.map(function (ch) { return (/\d/.test(ch) ? dw : c.measureText(ch).width) + ls; });
    var total = widths.reduce(function (a, w) { return a + w; }, 0);
    c.save();
    c.font = font; c.fillStyle = color; c.textBaseline = 'alphabetic'; c.textAlign = 'center';
    if (total > maxW) { c.translate(x, 0); c.scale(maxW / total, 1); c.translate(-x, 0); }
    chars.forEach(function (ch, i) { c.fillText(ch, x + (widths[i] - ls) / 2, y); x += widths[i]; });
    c.restore();
  }

  /** Garis rute (titik [lat, lng]) dipaskan ke kotak w × p.h, dengan penanda start/finish. */
  function routeMap(c, d, t, p, ox, w) {
    var pts = d.route, pad = 16, lat0 = 0;
    pts.forEach(function (q) { lat0 += q[0]; });
    var kx = Math.cos(lat0 / pts.length * Math.PI / 180);
    var xy = pts.map(function (q) { return [q[1] * kx, -q[0]]; });
    var xs = xy.map(function (q) { return q[0]; }), ys = xy.map(function (q) { return q[1]; });
    var x0 = Math.min.apply(null, xs), y0 = Math.min.apply(null, ys);
    var rw = (Math.max.apply(null, xs) - x0) || 1e-9, rh = (Math.max.apply(null, ys) - y0) || 1e-9;
    var s = Math.min((w - pad * 2) / rw, (p.h - pad * 2) / rh);
    var dx = ox + (w - rw * s) / 2, dy = p.y + (p.h - rh * s) / 2;
    var P = xy.map(function (q) { return [dx + (q[0] - x0) * s, dy + (q[1] - y0) * s]; });

    c.save();
    c.lineJoin = 'round'; c.lineCap = 'round';
    var line = function () { c.beginPath(); P.forEach(function (q, i) { if (i) c.lineTo(q[0], q[1]); else c.moveTo(q[0], q[1]); }); c.stroke(); };
    c.strokeStyle = 'rgba(0,0,0,.28)'; c.lineWidth = 16; line();
    c.strokeStyle = '#fff'; c.lineWidth = 6; line();

    var a = P[0], b = P[P.length - 1], loop = Math.hypot(a[0] - b[0], a[1] - b[1]) < 60;
    var mark = function (q, label) {
      c.fillStyle = '#fff'; c.beginPath(); c.arc(q[0], q[1], 13, 0, Math.PI * 2); c.fill();
      c.fillStyle = t.main; c.beginPath(); c.arc(q[0], q[1], 6, 0, Math.PI * 2); c.fill();
      // Label di sisi luar rute (menjauhi tengah) supaya tidak menimpa garis; kalau tak muat, di bawah titik.
      var font = fnt('n', 700, p.lf, BL), lw = tw(c, label, font, 3), right = q[0] > ox + w / 2;
      var fits = right ? q[0] + 24 + lw <= ox + w + 40 : q[0] - 24 - lw >= ox - 40;
      if (fits) text(c, label, q[0] + (right ? 24 : -24), q[1] + p.lf * 0.36, font, '#fff', { ls: 3, align: right ? 'left' : 'right' });
      else text(c, label, Math.min(Math.max(q[0], ox + lw / 2), ox + w - lw / 2), q[1] + 24 + p.lf, font, '#fff', { ls: 3, align: 'center' });
    };
    if (loop) mark(a, 'START · FINISH'); else { mark(b, 'FINISH'); mark(a, 'START'); }
    c.restore();
  }

  /* ---------- muat aset, gambar, unduh ---------- */

  function loadImage(src) {
    return new Promise(function (resolve) {
      if (!src) return resolve(null);
      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = function () { resolve(null); };
      img.src = src;
    });
  }

  function loadFonts(sample) {
    if (!document.fonts || !document.fonts.load) return Promise.resolve();
    var faces = ['italic 900 40px "Barlow Condensed"', '700 40px "Barlow Condensed"', '800 40px "Barlow Condensed"', '500 16px Barlow', '600 16px Barlow', '700 16px Barlow'];
    var all = Promise.all(faces.map(function (f) { return document.fonts.load(f, sample).catch(function () {}); }));
    // Font gagal dimuat (offline) → tetap gambar dengan font cadangan.
    return Promise.race([all, new Promise(function (r) { setTimeout(r, 8000); })]);
  }

  function render(d) {
    d.catGender = d.gender ? d.cat + ' / ' + d.gender : d.cat;
    var t = THEMES[d.theme] || THEMES.red, a = d.assets || {};
    var sample = [d.name, d.cat, d.tag, d.time, d.date, 'E-CERTIFICATE · ± #0123456789…'].join(' ');
    return Promise.all([
      loadFonts(sample), loadImage(a.gtr), loadImage(a.rungerBadge), loadImage(a.rungerWhite),
      Promise.all((a.sponsors || []).map(function (sp) { return loadImage(sp.src).then(function (img) { return { im: img, wide: !!sp.wide }; }); }))
    ]).then(function (r) {
      var im = { gtr: r[1], rungerBadge: r[2], rungerWhite: r[3], sponsors: r[4], gtrWhite: tinted(r[1], '#fff') };
      document.querySelectorAll('canvas[data-card]').forEach(function (cv) {
        var kind = cv.dataset.card, m = FORMATS[kind];
        // Sertifikat digambar 2× (2246×1588) supaya tajam saat dicetak A4.
        var k = kind === 'cert' ? 2 : 1, w = m ? m.W : 1123, h = m ? m.H : 794;
        cv.width = w * k; cv.height = h * k;
        var c = cv.getContext('2d');
        c.setTransform(k, 0, 0, k, 0, 0);
        if (m) drawSocial(c, d, t, im, k, m); else drawCert(c, d, t, im, k);
        cv.dataset.ready = '1';
      });
    });
  }

  function init() {
    var d = window.GTR_CARD;
    if (!d) return;
    var ready = render(d);
    document.querySelectorAll('[data-dl]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        ready.then(function () {
          var cv = document.querySelector('canvas[data-card="' + btn.dataset.dl + '"]');
          if (!cv) return;
          cv.toBlob(function (blob) {
            if (!blob) return;
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob); link.download = btn.dataset.name || 'gtr2026.png';
            document.body.appendChild(link); link.click(); link.remove();
            setTimeout(function () { URL.revokeObjectURL(link.href); }, 2000);
          }, 'image/png');
        });
      });
    });
  }

  window.GtrCards = { render: render };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
