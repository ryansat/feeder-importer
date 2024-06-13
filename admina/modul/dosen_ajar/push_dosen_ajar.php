<?php
include "../../inc/config.php";
include "../../lib/prosesupdate/ProgressUpdater.php";

$token = get_token();
$options = [
    'filename' => $_GET['jurusan'] . '_progress.json',
    'autoCalc' => true,
    'totalStages' => 1
];
$pu = new Manticorp\ProgressUpdater($options);

$stageOptions = [
    'name' => 'This AJAX process takes a long time',
    'message' => 'But this will keep the user updated on it\'s actual progress!',
    'totalItems' => $db->query(
        "select ajar_dosen.id as id_dosen_ajar,left(ajar_dosen.semester,4) as tahun,ajar_dosen.*,
        jurusan.kode_jurusan,jurusan.id_sms
        from ajar_dosen
        inner join jurusan on ajar_dosen.kode_jurusan=jurusan.kode_jurusan
        where jurusan.kode_jurusan='" . $_GET['jurusan'] . "' and
        ajar_dosen.semester='" . $_GET['sem'] . "' and status_error!=1 and nidn!=''"
    )->rowCount(),
];

$pu->nextStage($stageOptions);

$arr_data = $db->query(
    "select ajar_dosen.id as id_dosen_ajar,left(ajar_dosen.semester,4) as tahun,ajar_dosen.*,
    jurusan.kode_jurusan,jurusan.id_sms
    from ajar_dosen
    inner join jurusan on ajar_dosen.kode_jurusan=jurusan.kode_jurusan
    where jurusan.kode_jurusan='" . $_GET['jurusan'] . "' and
    ajar_dosen.semester='" . $_GET['sem'] . "' and status_error!=1 and nidn!=''"
);

$successCount = 0;
$errorCount = 0;
$errorMessages = [];

foreach ($arr_data as $value) {
    $nidn = trim($value->nidn);
    $kode_mk = trim($value->kode_mk);
    $kelas = trim($value->nama_kelas);
    $ren_tm = trim($value->rencana_tatap_muka);
    $rel_tm = trim($value->tatap_muka_real);
    $semester = trim($value->semester);
    $id_sms = $value->id_sms;
    $sks_ajar = trim($value->sks_ajar);
    $kode_prodi = $value->kode_jurusan;

    $filter = "nidn='" . $nidn . "'";
    $response = service_request([
        'act' => 'GetListDosen',
        'token' => $token,
        'filter' => $filter,
        'order' => '',
        'limit' => '',
        'offset' => ''
    ]);
    $id_sdm = !empty($response->data) ? $response->data[0]->id_dosen : '';

    if ($id_sdm == '') {
        ++$errorCount;
        $db->update('ajar_dosen', ['status_error' => 2, 'keterangan' => "NIDN tidak terdaftar di feeder</b>"], 'id', $value->id_dosen_ajar);
        $errorMessages[] = "NIDN tidak terdaftar di feeder</b>";
    } else {
        $filter = "id_dosen='" . $id_sdm . "' and id_tahun_ajaran='$value->tahun'";
        $response = service_request([
            'act' => 'GetListPenugasanSemuaDosen',
            'token' => $token,
            'filter' => $filter,
            'order' => '',
            'limit' => '',
            'offset' => ''
        ]);
        $id_reg_ptk = !empty($response->data) ? $response->data[0]->id_registrasi_dosen : '';

        if ($id_reg_ptk == '') {
            ++$errorCount;
            $db->update('ajar_dosen', ['status_error' => 2, 'keterangan' => "Dosen belum ada penugasan di Semester ini</b>"], 'id', $value->id_dosen_ajar);
            $errorMessages[] = "Dosen belum ada penugasan di Semester ini</b>";
        } else {
            $filter = "id_prodi='$id_sms' and trim(kode_mata_kuliah)='" . $kode_mk . "' AND trim(nama_kelas_kuliah)='" . $kelas . "' AND id_semester='" . $semester . "'";
            $response = service_request([
                'act' => 'GetDetailKelasKuliah',
                'token' => $token,
                'filter' => $filter,
                'order' => '',
                'limit' => '',
                'offset' => ''
            ]);
            $id_kls = !empty($response->data) ? $response->data[0]->id_kelas_kuliah : '';

            if ($id_kls == '') {
                ++$errorCount;
                $db->update('ajar_dosen', ['status_error' => 2, 'keterangan' => "Error, Pastikan Kelas $kelas Sudah dibuat "], 'id', $value->id_dosen_ajar);
                $errorMessages[] = "Error, Pastikan Kelas $kelas Sudah dibuat ";
            } else {
                if ($sks_ajar != NULL || $sks_ajar != '') {
                    $sks_mk = $sks_ajar;
                } else {
                    $response = service_request([
                        'act' => 'GetListMataKuliah',
                        'token' => $token,
                        'filter' => "id_matkul='" . $response->data[0]->id_matkul . "'"
                    ]);
                    $sks_mk = !empty($response->data) ? $response->data[0]->sks_mata_kuliah : '';

                    $checkCount = $db->query(
                        "select * from ajar_dosen where semester=? and kode_mk=? and nama_kelas=? and kode_jurusan=?",
                        ['semester' => $value->semester, 'kode_mk' => $value->kode_mk, 'nama_kelas' => $value->nama_kelas, 'kode_jurusan' => $kode_prodi]
                    );
                    if ($checkCount->rowCount() > 1) {
                        $sks_mk = $sks_mk / $checkCount->rowCount();
                    } else {
                        $sks_mk = !empty($response->data) ? $response->data[0]->sks_mata_kuliah : '';
                    }
                }

                if ($id_reg_ptk != '' && $id_kls != '') {
                    $response = service_request([
                        'act' => 'GetDosenPengajarKelasKuliah',
                        'token' => $token,
                        'filter' => "id_registrasi_dosen='$id_reg_ptk' and id_kelas_kuliah='$id_kls'"
                    ]);
                    if (empty($response->data)) {
                        $tempData = [
                            'id_registrasi_dosen' => $id_reg_ptk,
                            'id_kelas_kuliah' => $id_kls,
                            'sks_substansi_total' => $sks_mk,
                            'rencana_minggu_pertemuan' => $ren_tm,
                            'realisasi_minggu_

