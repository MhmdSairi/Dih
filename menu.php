<?php
require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/api/beli-produk.php';
require_once __DIR__ . '/api/cek-kuota.php';
require_once __DIR__ . '/api/otp.php';

// ANSI Color Codes
function color($text, $color) {
    $colors = [
        'reset' => "\033[0m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'magenta' => "\033[35m",
        'cyan' => "\033[36m",
        'white' => "\033[97m",
        'gray' => "\033[90m",
        'bg_blue' => "\033[44m",
        'bg_cyan' => "\033[46m",
        'bg_magenta' => "\033[45m",
        'bg_yellow' => "\033[43m",
        'bg_green' => "\033[42m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}
system('clear');
function banner() {
    $ascii = color("


╔═╗╔═╦╗─────╔═══╦═══╦═╗─╔╦═══╦╗
╚╗╚╝╔╣║─────║╔═╗║╔═╗║║╚╗║║╔══╣║
─╚╗╔╝║║─────║╚═╝║║─║║╔╗╚╝║╚══╣║
─╔╝╚╗║║─╔╦══╣╔══╣╚═╝║║╚╗║║╔══╣║─╔╗
╔╝╔╗╚╣╚═╝╠══╣║──║╔═╗║║─║║║╚══╣╚═╝║
╚═╝╚═╩═══╝──╚╝──╚╝─╚╩╝─╚═╩═══╩═══╝
", "cyan");

    $desc = color("──────────────────────────────────────────────", "yellow") . "\n";
    $desc .= color("      XL-PANEL | SISTEM TOPUP", "magenta") . "\n";
    $desc .= color("──────────────────────────────────────────────", "yellow");

    return $ascii . "\n" . $desc . "\n";
}

$user = auth();

echo banner();
echo color("\n=== INFORMASI USER ===\n", "yellow");
echo color("Username : {$user['username']}\n", "cyan");
echo color("Email    : {$user['email']}\n", "cyan");
echo color("Nomor WA : {$user['nomor_wa']}\n", "cyan");
echo color("Role     : {$user['role']}\n", "yellow");
echo color("Saldo    : Rp " . number_format($user['saldo'], 0, ',', '.') . "\n", "green");
echo color("=======================\n", "yellow");

while (true) {
    echo color("\nMenu:\n", "magenta");
    echo color(" 1. ", "yellow") . color("Lihat Produk & Beli\n", "green");
    echo color(" 2. ", "yellow") . color("Cek Kuota\n", "cyan");
    echo color(" 3. ", "yellow") . color("Request & Verifikasi OTP\n", "magenta");
    echo color(" 0. ", "red")    . color("Keluar\n", "yellow");
    echo color("Pilih: ", "white");

    $input = trim(fgets(STDIN));
    switch ($input) {
        case '1':
            require_once __DIR__ . '/api/produk.php';
            $products = getProdukList();

            $kategoriList = [];
            foreach ($products as $p) {
                $kategoriList[$p['kategori']] = true;
            }
            $kategoriList = array_keys($kategoriList);

            echo color("\n=== PILIH KATEGORI ===\n", "yellow");
            foreach ($kategoriList as $i => $kategori) {
                echo color(($i + 1) . ". $kategori\n", "cyan");
            }
            echo color("0. Kembali\n", "red");
            echo color("Pilih kategori: ", "white");
            $kategoriInput = trim(fgets(STDIN));

            if ($kategoriInput === '0') {
                break;
            }

            $kategoriIndex = (int)$kategoriInput - 1;
            if (!isset($kategoriList[$kategoriIndex])) {
                echo color("Kategori tidak valid.\n", "red");
                break;
            }

            $selectedKategori = $kategoriList[$kategoriIndex];
            echo color("\n=== PRODUK: {$selectedKategori} ===\n", "yellow");

            $filtered = array_values(array_filter($products, function ($p) use ($selectedKategori) {
                return $p['kategori'] === $selectedKategori;
            }));

            foreach ($filtered as $i => $p) {
                // Tentukan harga berdasarkan role
                $harga = 0;
                switch ($user['role']) {
                    case 'vip':
                    case 'admin':
                        $harga = (float)$p['harga_vip'];
                        break;
                    case 'reseller':
                        $harga = (float)$p['harga_reseller'];
                        break;
                    default:
                        $harga = (float)$p['harga_member'];
                }

                echo color(($i + 1) . ". {$p['nama_produk']}\n", "yellow");
                echo color("   Kode Produk  : ", "white") . color("{$p['produk_kode']}\n", "magenta");
                echo color("   Harga        : ", "white") . color("Rp " . number_format($harga, 0, ',', '.') . "\n", "green");
                echo color("   Status       : ", "white") . color("{$p['status']}\n", $p['status'] == 'active' ? "green" : "red");
                echo color("   Deskripsi    : ", "white") . color((trim($p['deskripsi']) ?: '-')."\n\n", "cyan");
            }

            echo color("Pilih nomor produk untuk dibeli (0 untuk batal): ", "yellow");
            $produkInput = trim(fgets(STDIN));
            $produkIndex = (int)$produkInput - 1;

            if ($produkInput === '0' || !isset($filtered[$produkIndex])) {
                echo color("Batal membeli.\n", "red");
                break;
            }

            $produkTerpilih = $filtered[$produkIndex];

            echo color("Masukkan nomor tujuan (contoh: 087812345678): ", "white");
            $nomorTujuan = trim(fgets(STDIN));

            echo color("\nPilih metode pembayaran:\n", "cyan");
            echo color(" 1. BALANCE (PULSA)\n", "green");
            echo color(" 2. DANA\n", "cyan");
            echo color(" 3. GOPAY\n", "blue");
            echo color(" 4. SHOPEEPAY\n", "magenta");
            echo color("Pilih: ", "white");
            $metodeInput = trim(fgets(STDIN));

            $paymentMap = [
                '1' => 'BALANCE',
                '2' => 'DANA',
                '3' => 'GOPAY',
                '4' => 'SHOPEEPAY',
            ];

            if (!isset($paymentMap[$metodeInput])) {
                echo color("Metode pembayaran tidak valid.\n", "red");
                break;
            }

            $metodePembayaran = $paymentMap[$metodeInput];

            switch ($user['role']) {
                case 'vip':
                case 'admin':
                    $harga = (float)$produkTerpilih['harga_vip'];
                    break;
                case 'reseller':
                    $harga = (float)$produkTerpilih['harga_reseller'];
                    break;
                default:
                    $harga = (float)$produkTerpilih['harga_member'];
            }

            echo color("\nMemproses transaksi...\n", "magenta");

            $response = beliProduk(
                $metodePembayaran,
                $nomorTujuan,
                $harga,
                $produkTerpilih['produk_kode'],
                $produkTerpilih['produk_kode']
            );

            echo color("\n=== HASIL TRANSAKSI ===\n", "yellow");
            echo color("Success    : ", "white") . color(($response['success'] ? 'SUCCESS' : 'FAILED') . "\n", $response['success'] ? "green" : "red");
            echo color("Message    : ", "white") . color(($response['transaction']['message'] ?? $response['message'] ?? '-') . "\n", "yellow");
            echo color("Trx ID     : ", "white") . color(($response['trx_id'] ?? '-') . "\n", "magenta");

            if (isset($response['transaction'])) {
                $trx = $response['transaction'];
                echo color("Status     : {$trx['status']}\n", "cyan");
                echo color("Produk     : {$trx['produk_nama']} ({$trx['produk_kode']})\n", "yellow");
                echo color("Harga      : Rp " . number_format($trx['harga'], 0, ',', '.') . "\n", "green");
                echo color("Metode     : {$trx['metode_pembayaran']}\n", "blue");
                echo color("Waktu      : {$trx['waktu']}\n", "cyan");
                echo color("Nomor Tujuan : {$trx['msisdn']}\n", "cyan");

                if (!empty($trx['link_pembayaran'])) {
                    echo color("🔗 Link Pembayaran:\n", "magenta") . color("{$trx['link_pembayaran']}\n", "blue");
                }

                echo color("Sisa Saldo : Rp " . number_format($trx['saldo'], 0, ',', '.') . "\n", "green");
            } else {
                echo color("⚠️ Detail transaksi tidak tersedia.\n", "red");
            }

            echo color("========================\n", "yellow");
            break;
        case '2':
            echo color("\n=== Cek Kuota ===\n", "bg_cyan");
            echo color("Masukkan nomor HP (contoh: 081234567890): ", "white");
            $msisdn = trim(fgets(STDIN));

            echo color("Memeriksa kuota...\n", "magenta");
            $kuota = cekKuota($msisdn);

            if (!$kuota || empty($kuota['success'])) {
                echo color("❌ Gagal mendapatkan data kuota.\n", "red");
                break;
            }

            $data = $kuota['data'];
            echo color("\nNomor     : {$data['msisdn']}\n", "cyan");
            echo color("Operator  : {$data['owner']}\n", "magenta");
            echo color("Status    : {$data['status']}\n", "yellow");
            echo color("Masa Aktif: {$data['expDate']}\n", "green");
            echo color("Kategori  : {$data['category']}\n", "blue");
            echo color("Tenure    : {$data['tenure']}\n", "gray");
            echo color("Update    : {$data['lastUpdate']}\n", "gray");
            echo color("--------------------------------------\n", "yellow");

            if (!empty($data['data'])) {
                foreach ($data['data'] as $paket) {
                    echo color("📦 Paket: {$paket['packageName']}\n", "yellow");
                    echo color("  Exp: {$paket['packageExpDate']}\n", "green");

                    if (!empty($paket['benefits'])) {
                        foreach ($paket['benefits'] as $b) {
                            echo color("   - {$b['name']} ({$b['type']}): {$b['remaining']} / {$b['quota']}\n", "cyan");
                        }
                    } else {
                        echo color("   - (Tidak ada detail kuota)\n", "gray");
                    }
                    echo "\n";
                }
            } else {
                echo color("Tidak ada paket aktif.\n", "red");
            }

            echo color("======================================\n", "yellow");
            break;

        case '3':
            echo color("\n=== OTP REQUEST ===\n", "bg_magenta");
            echo color("Masukkan nomor HP (contoh: 0878xxxxxxx): ", "white");
            $msisdn = trim(fgets(STDIN));

            $req = requestOtp($msisdn);
            if (!$req || !$req['success']) {
                echo color("❌ Gagal request OTP: " . ($req['message'] ?? 'Terjadi kesalahan') . "\n", "red");
                break;
            }

            echo color("✅ OTP dikirim! Masukkan kode OTP: ", "yellow");
            $otp = trim(fgets(STDIN));

            $verif = verifyOtp($msisdn, $otp);
            if (!$verif || !$verif['success']) {
                echo color("❌ Gagal verifikasi: " . ($verif['message'] ?? 'Terjadi kesalahan') . "\n", "red");
            } else {
                echo color("✅ OTP berhasil diverifikasi: " . $verif['message'] . "\n", "yellow");
            }
            break;

        case '0':
            echo color("Keluar...\n", "yellow");
            exit;

        default:
            echo color("Menu tidak valid.\n", "red");
    }
}