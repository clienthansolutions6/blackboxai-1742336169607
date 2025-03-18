<?php
function tanggal_indo($tanggal) {
    $bulan = array (
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    $split = explode('-', $tanggal);
    
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

function tanggal_indo_timestamp($timestamp) {
    $tanggal = date('Y-m-d', strtotime($timestamp));
    return tanggal_indo($tanggal);
}

function tanggal_indo_waktu($timestamp) {
    $tanggal = date('Y-m-d', strtotime($timestamp));
    $waktu = date('H:i', strtotime($timestamp));
    return tanggal_indo($tanggal) . ' ' . $waktu;
}

function bulan_indo($bulan_angka) {
    $bulan = array (
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    return $bulan[$bulan_angka];
}

function hari_indo($hari_inggris) {
    $hari = array (
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );
    
    return $hari[$hari_inggris];
}

function tanggal_indo_lengkap($timestamp) {
    $hari = date('l', strtotime($timestamp));
    $tanggal = date('Y-m-d', strtotime($timestamp));
    return hari_indo($hari) . ', ' . tanggal_indo($tanggal);
}

function tanggal_indo_lengkap_waktu($timestamp) {
    $hari = date('l', strtotime($timestamp));
    $tanggal = date('Y-m-d', strtotime($timestamp));
    $waktu = date('H:i', strtotime($timestamp));
    return hari_indo($hari) . ', ' . tanggal_indo($tanggal) . ' ' . $waktu;
}

function selisih_hari($tanggal_awal, $tanggal_akhir) {
    $awal = strtotime($tanggal_awal);
    $akhir = strtotime($tanggal_akhir);
    $selisih = $akhir - $awal;
    return floor($selisih / (60 * 60 * 24));
}
?>