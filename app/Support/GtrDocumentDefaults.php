<?php

namespace App\Support;

/**
 * Isi awal dokumen peserta GTR (Waiver, PAR-Q, Surat Kuasa) dalam format blok Filament Builder.
 * Dipakai sekali saat migrasi; setelah itu isi diedit lewat admin (menu GTR → Dokumen Peserta).
 *
 * Penulisan isian di blok "Isian", "Pilihan" & "Tanda Tangan":
 *   ___   (3 garis bawah atau lebih) → garis isian pendek di posisi tersebut
 *   [ ]   → kotak centang
 *   baris tanpa ___ → label + garis isian sampai ujung kolom
 */
class GtrDocumentDefaults
{
    private static function b(string $type, array $data, string $column = 'full'): array
    {
        return ['type' => $type, 'data' => $data + ['column' => $column]];
    }

    private static function lines(array $lines): string
    {
        return implode("\n", $lines);
    }

    public static function all(): array
    {
        return [
            self::waiver(),
            self::parq(),
            self::kuasa(),
        ];
    }

    private static function meta(bool $en): string
    {
        return $en
            ? self::lines(['Sunday, 29 November 2026', 'Keteri Hill · Gerung, West Lombok', '7K & 15K Categories'])
            : self::lines(['Minggu, 29 November 2026', 'Bukit Keteri · Gerung, Lombok Barat', 'Kategori 7K & 15K']);
    }

    // ===================== WAIVER =====================

    private static function waiver(): array
    {
        $ev = 'GERUNG TRAIL RUN 2026';

        $id = [
            self::b('paragraph', ['text' => "Sehubungan dengan keikutsertaan saya dalam event lari trail {$ev}, saya menyatakan bahwa saat ini saya dalam keadaan sehat jasmani dan rohani dan tidak sedang dalam larangan untuk mengikuti kegiatan yang menguras fisik, termasuk di dalamnya event {$ev}. Saya, sebagai peserta, dengan ini mengakui dan menyetujui hal-hal berikut:"]),
            self::b('statements', ['style' => 'number', 'items' => self::lines([
                "Saya dalam kondisi fisik dan mental yang sehat serta mampu secara medis untuk mengikuti {$ev}. Saya tidak memiliki kondisi kesehatan yang dapat membahayakan diri saya atau orang lain selama kegiatan berlangsung. Saya juga menyatakan telah berlatih dengan cukup dan akan mengundurkan diri apabila mengetahui adanya kondisi medis yang berisiko. Saya bersedia mengisi formulir screening kesehatan (PAR-Q) dan menyerahkannya saat pengambilan race pack, serta menyediakan surat keterangan sehat apabila diminta panitia.",
                'Saya setuju untuk tidak berpartisipasi dalam kondisi yang tidak layak, serta tidak mengonsumsi alkohol, obat-obatan, atau zat apa pun yang dapat mengganggu kemampuan fisik maupun mental saya sebelum dan selama acara.',
                'Saya memahami dan menyadari bahwa partisipasi dalam lomba lari lintas alam ini memiliki risiko tinggi, termasuk namun tidak terbatas pada cedera, terjatuh, tabrakan, jalur licin atau curam, penyeberangan sungai dan aliran air, kondisi cuaca ekstrem, kontak dengan peserta lain atau hewan, hingga risiko kematian. Saya secara sukarela menanggung seluruh risiko tersebut, baik yang terjadi sebelum, selama, maupun setelah acara.',
                "Saya menyatakan bahwa segala bentuk cedera, kerusakan, kehilangan, atau risiko lainnya yang saya alami merupakan tanggung jawab pribadi saya sepenuhnya. Saya juga setuju untuk melepaskan, membebaskan, dan tidak akan menuntut penyelenggara {$ev}, Runners Gerung (Runger), beserta seluruh pihak terkait (termasuk panitia, marshal, relawan, sponsor, dan mitra) atas segala klaim, kerugian, cedera, atau kematian yang mungkin timbul akibat partisipasi saya, baik karena kelalaian maupun sebab lainnya.",
                'Saya setuju untuk memberikan ganti rugi dan membebaskan penyelenggara serta pihak terkait dari segala tuntutan yang timbul akibat tindakan atau kelalaian saya selama mengikuti kegiatan.',
                'Saya menyetujui untuk menerima tindakan medis darurat apabila diperlukan, termasuk evakuasi dan transportasi medis. Saya memahami bahwa biaya yang timbul dari tindakan tersebut menjadi tanggung jawab pribadi saya, termasuk pengaturan asuransi kesehatan.',
                'Saya setuju untuk mematuhi seluruh peraturan, ketentuan, dan arahan yang ditetapkan oleh penyelenggara, marshal, dan tim medis, termasuk keputusan resmi terkait keselamatan, batas waktu (cut off), dan kemampuan saya untuk melanjutkan lomba. Saya juga memahami bahwa penyelenggara berhak menolak, menangguhkan, atau mendiskualifikasi partisipasi saya apabila dianggap membahayakan diri sendiri, orang lain, atau jalannya acara.',
                'Saya setuju untuk selalu berperilaku baik, menghormati sesama peserta, panitia, warga sekitar, fasilitas, serta lingkungan. Saya tidak akan membuang sampah di sepanjang jalur, akan membawa sampah saya sampai garis finish, dan tidak akan merusak area perlombaan. Pelanggaran terhadap hal ini dapat berakibat diskualifikasi tanpa pengembalian biaya.',
                'Saya memahami bahwa penyelenggara berhak melakukan perubahan rute, jadwal, atau aspek teknis acara apabila diperlukan demi keselamatan dan kelancaran acara.',
                'Saya menyatakan bahwa seluruh perlengkapan wajib sesuai kategori akan saya bawa dan gunakan selama kegiatan berlangsung, serta memasang nomor BIB di bagian depan tubuh agar terlihat jelas.',
                'Saya memahami bahwa pendaftaran bersifat final, tidak dapat dipindahkan kepada pihak lain, dan tidak dapat dikembalikan (non-refundable).',
                'Saya menyetujui bahwa penyelenggara tidak bertanggung jawab atas kehilangan atau pencurian barang pribadi selama acara berlangsung.',
                'Saya memberikan izin kepada penyelenggara untuk menggunakan nama, foto, video, dan dokumentasi lainnya dari partisipasi saya untuk keperluan promosi, publikasi, dan pemasaran tanpa kompensasi, dan seluruh dokumentasi tersebut menjadi milik penyelenggara.',
                'Saya menyatakan bahwa saya telah membaca, memahami, dan menyetujui seluruh isi pernyataan ini dengan penuh kesadaran dan tanpa paksaan dari pihak mana pun. Saya juga menyatakan bahwa seluruh informasi yang saya berikan adalah benar dan sah, serta saya menandatangani dokumen ini secara jujur, tanpa adanya manipulasi, pemalsuan, atau penyalahgunaan identitas dalam bentuk apa pun.',
            ])]),
            self::b('signature', ['panels' => [
                ['title' => 'Data Pelari', 'fields' => self::lines(['Nama Lengkap Pelari', 'Nomor BIB ________  Kategori  [ ] 7K   [ ] 15K', 'No. HP / WhatsApp', 'Riwayat Penyakit']), 'sign_label' => 'Tanda Tangan Pelari', 'note' => ''],
                ['title' => 'Kontak Darurat / Ahli Waris / Wali', 'fields' => self::lines(['Nama Ahli Waris / Wali', 'Hubungan dengan Pelari', 'No. HP / WhatsApp']), 'sign_label' => 'Tanda Tangan Ahli Waris / Wali', 'note' => 'Wajib ditandatangani orang tua/wali untuk peserta di bawah 17 tahun.'],
            ]]),
            self::b('fields', ['items' => 'Tempat, Tanggal', 'align' => 'center']),
        ];

        $en = [
            self::b('paragraph', ['text' => "Regarding my participation in the {$ev} trail running event, I hereby declare that I am currently in good physical and mental health and not restricted from participating in physically demanding activities, including the {$ev} event. As a participant, I acknowledge and agree to the following:"]),
            self::b('statements', ['style' => 'number', 'items' => self::lines([
                "I am in sound physical and mental condition and am medically fit to participate in {$ev}. I do not have any health conditions that could endanger myself or others during the event. I affirm that I have trained sufficiently and will withdraw should I become aware of any high-risk medical conditions. I agree to complete the health screening form (PAR-Q) and submit it at race pack collection, and to provide a medical certificate if requested by the organizers.",
                'I agree not to participate if I am unfit to do so. I will not consume alcohol, illegal drugs, or any substances that may impair my physical or mental abilities before or during the event.',
                'I understand that participating in a trail running race involves significant risks, including but not limited to: injury, falls, collisions, slippery or steep terrain, river and stream crossings, extreme weather conditions, contact with other participants or animals, and the risk of death. I voluntarily assume all such risks occurring before, during, or after the event.',
                "I declare that any injury, damage, loss, or other risks I experience are entirely my personal responsibility. I hereby release, waive, and discharge the organizers of {$ev}, Runners Gerung (Runger), and all related parties (including committee members, marshals, volunteers, sponsors, and partners) from any claims, losses, injuries, or death arising from my participation, whether caused by negligence or otherwise.",
                'I agree to indemnify and hold harmless the organizers and related parties from any claims or demands arising from my own actions or omissions during the event.',
                'I consent to receive emergency medical treatment if necessary, including evacuation and medical transport. I understand that all costs arising from such actions are my personal responsibility, including health insurance arrangements.',
                "I agree to abide by all rules, terms, and directions set by the organizers, marshals, and medical team, including official decisions regarding safety, cut-off times, and my ability to continue the race. The organizers reserve the right to refuse, suspend, or disqualify my participation if I am deemed a danger to myself, others, or the event's integrity.",
                'I agree to conduct myself appropriately and respect fellow participants, staff, local residents, facilities, and the environment. I will not litter along the course, will carry my waste to the finish line, and will not damage the race area. Violations may result in disqualification without a refund.',
                'I understand that the organizers reserve the right to change the route, schedule, or technical aspects of the event if necessary for safety or operational reasons.',
                'I declare that I will carry and use all mandatory equipment for my category throughout the duration of the event, and will wear my bib number visibly on the front of my body.',
                'I understand that registration is final, non-transferable to other parties, and non-refundable.',
                'I agree that the organizers are not responsible for the loss or theft of personal belongings during the event.',
                'I grant the organizers permission to use my name, photos, videos, and other documentation of my participation for promotion, publication, and marketing purposes without compensation. All such documentation shall remain the property of the organizers.',
                'I state that I have read, understood, and agreed to the entirety of this statement voluntarily and without coercion. I certify that all information provided is true and valid, and I sign this document honestly, without any manipulation or falsification of identity.',
            ])]),
            self::b('signature', ['panels' => [
                ['title' => 'Runner Data', 'fields' => self::lines(["Runner's Full Name", 'Bib Number ________  Category  [ ] 7K   [ ] 15K', 'Phone / WhatsApp', 'Medical History']), 'sign_label' => "Runner's Signature", 'note' => ''],
                ['title' => 'Emergency Contact / Guardian', 'fields' => self::lines(['Next of Kin / Guardian Name', 'Relationship to Runner', 'Phone / WhatsApp']), 'sign_label' => 'Signature of Next of Kin / Guardian', 'note' => 'Must be signed by a parent/guardian for runners under 17 years old.'],
            ]]),
            self::b('fields', ['items' => 'Place, Date', 'align' => 'center']),
        ];

        return [
            'slug' => 'waiver',
            'nav_label' => 'Waiver',
            'title_id' => 'Surat Pernyataan Pembebasan Tanggung Jawab',
            'title_en' => 'Participant Declaration and Waiver of Liability',
            'meta_id' => self::meta(false),
            'meta_en' => self::meta(true),
            'footer_id' => 'Serahkan formulir yang sudah ditandatangani saat pengambilan race pack (RPC)',
            'footer_en' => 'Submit this signed form at Race Pack Collection (RPC)',
            'font_size' => 90,
            'has_en' => true,
            'sort_order' => 1,
            'content_id' => $id,
            'content_en' => $en,
        ];
    }

    // ===================== PAR-Q =====================

    private static function parq(): array
    {
        $id = [
            self::b('heading', ['text' => 'A. Data Diri'], 'left'),
            self::b('fields', ['items' => self::lines([
                'Nama Lengkap', 'Nomor BIB ________  Kategori  [ ] 7K   [ ] 15K', 'Jenis Kelamin  [ ] Laki-laki   [ ] Perempuan',
                'Tanggal Lahir / Usia', 'Tinggi / Berat Badan', 'Golongan Darah', 'Nomor HP Aktif', 'Email',
            ])], 'left'),
            self::b('heading', ['text' => 'B. Riwayat Kesehatan'], 'left'),
            self::b('checks', ['label' => '1. Riwayat penyakit (pernah / sedang) — beri tanda centang yang sesuai:', 'cols' => 2, 'options' => self::lines([
                'Penyakit Jantung', 'Hipertensi', 'Diabetes', 'Asma / Gangguan Napas', 'Epilepsi', 'Cedera Lutut / Pergelangan', 'Cedera Tulang Belakang', 'Alergi: ________', 'Penyakit lain: ________',
            ])], 'left'),
            self::b('checks', ['label' => '2. Riwayat operasi:', 'cols' => 2, 'options' => self::lines(['Tidak ada', 'Ada'])], 'left'),
            self::b('fields', ['items' => 'Jika ada, sebutkan & tahun'], 'left'),
            self::b('heading', ['text' => 'C. Kondisi Saat Ini'], 'left'),
            self::b('yesno', ['questions' => self::lines([
                'Sedang dalam pengawasan dokter?', 'Sedang mengonsumsi obat rutin?', 'Memiliki alergi obat / makanan?',
            ])], 'left'),
            self::b('fields', ['items' => 'Jika Ya, sebutkan'], 'left'),
            self::b('heading', ['text' => 'D. Screening Fisik (Wajib · PAR-Q Extended)'], 'left'),
            self::b('yesno', ['questions' => self::lines([
                'Pernah didiagnosis penyakit jantung oleh dokter?',
                'Sering nyeri dada saat beraktivitas?',
                'Nyeri dada saat istirahat dalam 12 bulan terakhir?',
                'Pernah pingsan atau kehilangan keseimbangan?',
                'Memiliki penyakit kronis?',
                'Cedera tulang/sendi yang bisa memburuk saat aktivitas?',
                'Mengonsumsi obat jantung / tekanan darah?',
                'Pernah heatstroke / dehidrasi berat saat olahraga?',
                'Pernah kram parah hingga tidak bisa melanjutkan aktivitas?',
                'Pernah rhabdomyolysis / urine gelap setelah olahraga berat?',
            ])], 'left'),

            self::b('heading', ['text' => 'E. Riwayat Aktivitas & Kesiapan Fisik'], 'right'),
            self::b('fields', ['items' => self::lines([
                'Frekuensi latihan per minggu: ______ kali',
                'Jenis latihan:  [ ] Lari  [ ] Gym  [ ] Sepeda  [ ] Hiking',
                'Jarak terjauh pernah ditempuh: ______ km',
                'Elevasi tertinggi pernah ditempuh: ______ m',
                'Event lari terakhir yang diikuti',
                'Estimasi waktu finish di kategori yang diikuti',
            ])], 'right'),
            self::b('heading', ['text' => 'F. Data Khusus Trail Running'], 'right'),
            self::b('yesno', ['questions' => self::lines([
                'Pernah ikut trail run sebelumnya?',
                'Pernah berlari di tanjakan / turunan curam?',
                'Pernah berlari di cuaca panas lebih dari 2 jam?',
            ])], 'right'),
            self::b('checks', ['label' => 'Saya memahami risiko berikut:', 'cols' => 2, 'options' => self::lines([
                'Dehidrasi', 'Heatstroke / kelelahan panas', 'Cedera medan teknis', 'Keterlambatan evakuasi',
            ])], 'right'),
            self::b('heading', ['text' => 'G. Data Medis Tambahan'], 'right'),
            self::b('fields', ['items' => self::lines([
                'Tekanan darah terakhir (jika ada)',
                'Denyut nadi istirahat: ______ bpm',
                'Riwayat COVID / penyakit paru',
                'Catatan medis penting lain',
            ])], 'right'),
            self::b('heading', ['text' => 'H. Persetujuan Peserta'], 'right'),
            self::b('statements', ['style' => 'bullet', 'items' => self::lines([
                'Saya menyatakan bahwa data yang saya berikan adalah benar dan tanpa manipulasi. Saya dalam kondisi sehat, siap mengikuti perlombaan, serta memahami dan menerima seluruh risiko yang mungkin terjadi.',
                'Saya bersedia mematuhi seluruh peraturan, arahan panitia, dan keputusan tim medis, serta menyetujui tindakan medis darurat jika diperlukan.',
                'Saya dengan ini melepaskan penyelenggara dan pihak terkait dari segala tuntutan atas risiko atau kerugian selama partisipasi saya.',
                'Saya telah membaca, memahami, dan menyetujui pernyataan ini dengan penuh kesadaran, tanpa paksaan, dan menandatanganinya secara sah.',
            ])], 'right'),
            self::b('signature', ['panels' => [
                ['title' => '', 'fields' => self::lines(['Nama', 'Tanggal']), 'sign_label' => 'Tanda Tangan', 'note' => ''],
            ]], 'right'),
        ];

        $en = [
            self::b('heading', ['text' => 'A. Personal Data'], 'left'),
            self::b('fields', ['items' => self::lines([
                'Full Name', 'Bib Number ________  Category  [ ] 7K   [ ] 15K', 'Gender  [ ] Male   [ ] Female',
                'Date of Birth / Age', 'Height / Weight', 'Blood Type', 'Mobile Number', 'Email',
            ])], 'left'),
            self::b('heading', ['text' => 'B. Medical History'], 'left'),
            self::b('checks', ['label' => '1. Medical conditions (past / current) — check where applicable:', 'cols' => 2, 'options' => self::lines([
                'Heart Disease', 'Hypertension', 'Diabetes', 'Asthma / Respiratory', 'Epilepsy', 'Knee / Ankle Injury', 'Spinal / Back Injury', 'Allergies: ________', 'Other: ________',
            ])], 'left'),
            self::b('checks', ['label' => '2. Surgical history:', 'cols' => 2, 'options' => self::lines(['None', 'Yes'])], 'left'),
            self::b('fields', ['items' => 'If yes, specify & year'], 'left'),
            self::b('heading', ['text' => 'C. Current Condition'], 'left'),
            self::b('yesno', ['questions' => self::lines([
                "Currently under a doctor's care?", 'Currently on routine medication?', 'Any food or drug allergies?',
            ])], 'left'),
            self::b('fields', ['items' => 'If Yes, please specify'], 'left'),
            self::b('heading', ['text' => 'D. Physical Screening (PAR-Q Extended)'], 'left'),
            self::b('yesno', ['questions' => self::lines([
                'Ever diagnosed with a heart condition by a doctor?',
                'Frequent chest pain during physical activity?',
                'Chest pain at rest in the past 12 months?',
                'Ever fainted or lost balance / consciousness?',
                'Any chronic disease?',
                'Bone or joint problem worsened by activity?',
                'Prescribed drugs for blood pressure / heart?',
                'Ever had heatstroke or severe dehydration in exercise?',
                'Ever had severe cramps that stopped an activity?',
                'Ever had rhabdomyolysis / dark urine after hard exercise?',
            ])], 'left'),

            self::b('heading', ['text' => 'E. Activity History & Readiness'], 'right'),
            self::b('fields', ['items' => self::lines([
                'Training frequency per week: ______ times',
                'Types of training:  [ ] Running  [ ] Gym  [ ] Cycling  [ ] Hiking',
                'Longest distance ever covered: ______ km',
                'Highest elevation gain ever achieved: ______ m',
                'Most recent running event joined',
                'Estimated finish time for your category',
            ])], 'right'),
            self::b('heading', ['text' => 'F. Trail Running Specific Data'], 'right'),
            self::b('yesno', ['questions' => self::lines([
                'Participated in a trail run before?',
                'Experience running steep climbs / descents?',
                'Experience running in hot weather for over 2 hours?',
            ])], 'right'),
            self::b('checks', ['label' => 'I understand the following risks:', 'cols' => 2, 'options' => self::lines([
                'Dehydration', 'Heatstroke / heat exhaustion', 'Technical terrain hazards', 'Delayed evacuation',
            ])], 'right'),
            self::b('heading', ['text' => 'G. Additional Medical Data'], 'right'),
            self::b('fields', ['items' => self::lines([
                'Last blood pressure reading (if any)',
                'Resting heart rate: ______ bpm',
                'History of COVID-19 / lung disease',
                'Other important medical notes',
            ])], 'right'),
            self::b('heading', ['text' => 'H. Participant Declaration'], 'right'),
            self::b('statements', ['style' => 'bullet', 'items' => self::lines([
                'I declare that all information provided is true and accurate. I am in good health, physically prepared to compete, and fully understand and accept all potential risks involved.',
                'I agree to abide by all rules, instructions from the committee, and medical staff decisions, and I consent to emergency medical treatment if required.',
                'I hereby release the organizers and all related parties from any liability or claims arising from risks or losses incurred during my participation.',
                'I have read, understood, and agreed to this statement voluntarily, without any coercion, and sign it legally.',
            ])], 'right'),
            self::b('signature', ['panels' => [
                ['title' => '', 'fields' => self::lines(['Name', 'Date']), 'sign_label' => 'Signature', 'note' => ''],
            ]], 'right'),
        ];

        return [
            'slug' => 'par-q',
            'nav_label' => 'PAR-Q',
            'title_id' => 'Surat Screening Kesehatan / PAR-Q',
            'title_en' => 'Health Screening / PAR-Q Form',
            'meta_id' => self::meta(false),
            'meta_en' => self::meta(true),
            'footer_id' => 'Serahkan formulir yang sudah diisi & ditandatangani saat pengambilan race pack (RPC)',
            'footer_en' => 'Submit this completed & signed form at Race Pack Collection (RPC)',
            'font_size' => 100,
            'has_en' => true,
            'sort_order' => 2,
            'content_id' => $id,
            'content_en' => $en,
        ];
    }

    // ===================== SURAT KUASA =====================

    private static function kuasa(): array
    {
        $id = [
            self::b('paragraph', ['text' => "Kepada Panitia Gerung Trail Run 2026,\n\nSaya yang bertanda tangan di bawah ini (Pemberi Kuasa / Peserta):"]),
            self::b('fields', ['items' => self::lines([
                'Nama Lengkap',
                'Nomor Identitas (KTP/SIM/Paspor/KITAS)',
                'Nomor BIB ________  Kategori  [ ] 7K   [ ] 15K',
                'No. HP / WhatsApp',
            ])]),
            self::b('paragraph', ['text' => 'Dengan ini memberikan kuasa kepada (Penerima Kuasa):']),
            self::b('fields', ['items' => self::lines([
                'Nama Lengkap',
                'Nomor Identitas (KTP/SIM/Paspor/KITAS)',
                'No. HP / WhatsApp',
                'Hubungan dengan Peserta',
            ])]),
            self::b('paragraph', ['text' => "untuk mengambil Race Pack saya pada Race Pack Collection (RPC) Gerung Trail Run 2026.\n\nBersamaan dengan surat ini, saya melampirkan dokumen yang diperlukan untuk pengambilan Race Pack:"]),
            self::b('statements', ['style' => 'bullet', 'items' => self::lines([
                'Foto / fotokopi kartu identitas peserta (KTP/SIM/Paspor/KITAS).',
                'E-ticket (QR) pendaftaran peserta dari email atau aplikasi GTR.',
                'Surat Screening Kesehatan (PAR-Q) dan Surat Pernyataan (Waiver) yang sudah diisi dan ditandatangani peserta.',
            ])]),
            self::b('paragraph', ['text' => "Sebelum mengambil Race Pack, penerima kuasa akan menunjukkan identitas asli untuk keperluan verifikasi. Saya memahami bahwa apabila ada dokumen di atas yang tidak dipenuhi, panitia berhak menolak pengambilan Race Pack saya dalam keadaan apa pun.\n\nDengan ini saya menyatakan bahwa Surat Kuasa ini beserta dokumen pendukungnya adalah benar dan sah."]),
            self::b('signature', ['panels' => [
                ['title' => 'Pemberi Kuasa (Peserta)', 'fields' => 'Tanggal', 'sign_label' => 'Tanda Tangan & Nama Jelas', 'note' => ''],
                ['title' => 'Penerima Kuasa', 'fields' => 'Tanggal', 'sign_label' => 'Tanda Tangan & Nama Jelas', 'note' => ''],
            ]]),
        ];

        $en = [
            self::b('paragraph', ['text' => "To the Committee of Gerung Trail Run 2026,\n\nI, the undersigned (Grantor / Participant):"]),
            self::b('fields', ['items' => self::lines([
                'Full Name',
                'ID Number (KTP/SIM/Passport/KITAS)',
                'Bib Number ________  Category  [ ] 7K   [ ] 15K',
                'Phone / WhatsApp',
            ])]),
            self::b('paragraph', ['text' => 'Hereby authorize (Representative):']),
            self::b('fields', ['items' => self::lines([
                'Full Name',
                'ID Number (KTP/SIM/Passport/KITAS)',
                'Phone / WhatsApp',
                'Relationship to Participant',
            ])]),
            self::b('paragraph', ['text' => "to collect my Race Pack on my behalf at the Race Pack Collection (RPC) of Gerung Trail Run 2026.\n\nAlong with this letter are the required documents for the Race Pack Collection:"]),
            self::b('statements', ['style' => 'bullet', 'items' => self::lines([
                "Photo / copy of the participant's ID (KTP/SIM/Passport/KITAS).",
                "The participant's registration e-ticket (QR) from email or the GTR app.",
                'Health Screening (PAR-Q) and Waiver forms, completed and signed by the participant.',
            ])]),
            self::b('paragraph', ['text' => "Before collecting the race pack, my representative will present an original photo ID for verification purposes. I understand that in the absence of any of the documents above, the committee has the right to decline my race pack collection under any circumstances.\n\nI hereby declare that this Authorization Letter, along with the supporting documents, is true and valid."]),
            self::b('signature', ['panels' => [
                ['title' => 'Grantor (Participant)', 'fields' => 'Date', 'sign_label' => 'Signature & Full Name', 'note' => ''],
                ['title' => 'Representative', 'fields' => 'Date', 'sign_label' => 'Signature & Full Name', 'note' => ''],
            ]]),
        ];

        return [
            'slug' => 'surat-kuasa',
            'nav_label' => 'Surat Kuasa',
            'title_id' => 'Surat Kuasa Pengambilan Race Pack',
            'title_en' => 'Race Pack Collection Authorization Letter',
            'meta_id' => self::meta(false),
            'meta_en' => self::meta(true),
            'footer_id' => 'Dibawa oleh penerima kuasa saat pengambilan race pack (RPC)',
            'footer_en' => 'To be brought by the representative at Race Pack Collection (RPC)',
            'font_size' => 115,
            'has_en' => true,
            'sort_order' => 3,
            'content_id' => $id,
            'content_en' => $en,
        ];
    }
}
