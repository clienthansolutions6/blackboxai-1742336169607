<?php
// Define menu structure based on user roles
$menu = [
    'admin' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'link' => '?module=dashboard'
        ],
        [
            'title' => 'Master Data',
            'icon' => 'fas fa-database',
            'submenu' => [
                [
                    'title' => 'Data Barang',
                    'icon' => 'fas fa-boxes',
                    'link' => '?module=barang'
                ],
                [
                    'title' => 'Jenis Barang',
                    'icon' => 'fas fa-tags',
                    'link' => '?module=jenis'
                ],
                [
                    'title' => 'Satuan',
                    'icon' => 'fas fa-ruler',
                    'link' => '?module=satuan'
                ],
                [
                    'title' => 'Lokasi',
                    'icon' => 'fas fa-map-marker-alt',
                    'link' => '?module=lokasi'
                ],
                [
                    'title' => 'Pengguna',
                    'icon' => 'fas fa-users',
                    'link' => '?module=user'
                ]
            ]
        ],
        [
            'title' => 'Transaksi',
            'icon' => 'fas fa-exchange-alt',
            'submenu' => [
                [
                    'title' => 'Barang Masuk',
                    'icon' => 'fas fa-arrow-circle-down',
                    'link' => '?module=barang-masuk'
                ],
                [
                    'title' => 'Barang Keluar',
                    'icon' => 'fas fa-arrow-circle-up',
                    'link' => '?module=barang-keluar'
                ]
            ]
        ],
        [
            'title' => 'Pengajuan',
            'icon' => 'fas fa-file-alt',
            'submenu' => [
                [
                    'title' => 'Permintaan Barang',
                    'icon' => 'fas fa-hand-holding',
                    'link' => '?module=permintaan'
                ],
                [
                    'title' => 'Peminjaman',
                    'icon' => 'fas fa-handshake',
                    'link' => '?module=peminjaman'
                ]
            ]
        ],
        [
            'title' => 'Laporan',
            'icon' => 'fas fa-chart-bar',
            'submenu' => [
                [
                    'title' => 'Stok Barang',
                    'icon' => 'fas fa-warehouse',
                    'link' => '?module=laporan-stok'
                ],
                [
                    'title' => 'Barang Masuk',
                    'icon' => 'fas fa-arrow-circle-down',
                    'link' => '?module=laporan-barang-masuk'
                ],
                [
                    'title' => 'Barang Keluar',
                    'icon' => 'fas fa-arrow-circle-up',
                    'link' => '?module=laporan-barang-keluar'
                ]
            ]
        ]
    ],
    'kepala_gudang' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'link' => '?module=dashboard'
        ],
        [
            'title' => 'Laporan',
            'icon' => 'fas fa-chart-bar',
            'submenu' => [
                [
                    'title' => 'Stok Barang',
                    'icon' => 'fas fa-warehouse',
                    'link' => '?module=laporan-stok'
                ],
                [
                    'title' => 'Barang Masuk',
                    'icon' => 'fas fa-arrow-circle-down',
                    'link' => '?module=laporan-barang-masuk'
                ],
                [
                    'title' => 'Barang Keluar',
                    'icon' => 'fas fa-arrow-circle-up',
                    'link' => '?module=laporan-barang-keluar'
                ]
            ]
        ]
    ],
    'mahasiswa' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'link' => '?module=dashboard'
        ],
        [
            'title' => 'Pengajuan',
            'icon' => 'fas fa-file-alt',
            'submenu' => [
                [
                    'title' => 'Permintaan Barang',
                    'icon' => 'fas fa-hand-holding',
                    'link' => '?module=permintaan'
                ],
                [
                    'title' => 'Peminjaman',
                    'icon' => 'fas fa-handshake',
                    'link' => '?module=peminjaman'
                ]
            ]
        ]
    ],
    'dosen' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'link' => '?module=dashboard'
        ],
        [
            'title' => 'Pengajuan',
            'icon' => 'fas fa-file-alt',
            'submenu' => [
                [
                    'title' => 'Permintaan Barang',
                    'icon' => 'fas fa-hand-holding',
                    'link' => '?module=permintaan'
                ],
                [
                    'title' => 'Peminjaman',
                    'icon' => 'fas fa-handshake',
                    'link' => '?module=peminjaman'
                ]
            ]
        ]
    ],
    'staff' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'link' => '?module=dashboard'
        ],
        [
            'title' => 'Pengajuan',
            'icon' => 'fas fa-file-alt',
            'submenu' => [
                [
                    'title' => 'Permintaan Barang',
                    'icon' => 'fas fa-hand-holding',
                    'link' => '?module=permintaan'
                ],
                [
                    'title' => 'Peminjaman',
                    'icon' => 'fas fa-handshake',
                    'link' => '?module=peminjaman'
                ]
            ]
        ]
    ]
];

// Get user's role from session
$userRole = $_SESSION['role'];

// Function to render menu items
function renderMenuItem($item) {
    if (isset($item['submenu'])) {
        // Render menu with submenu
        echo '<div class="py-2">';
        echo '<div class="px-4 py-2 text-sm font-medium text-gray-600">';
        echo '<i class="' . $item['icon'] . ' mr-2"></i>' . $item['title'];
        echo '</div>';
        echo '<div class="pl-4">';
        foreach ($item['submenu'] as $submenu) {
            echo '<a href="' . $submenu['link'] . '" class="sidebar-link block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white rounded-md transition-colors duration-200">';
            echo '<i class="' . $submenu['icon'] . ' mr-2"></i>' . $submenu['title'];
            echo '</a>';
        }
        echo '</div>';
        echo '</div>';
    } else {
        // Render single menu item
        echo '<a href="' . $item['link'] . '" class="sidebar-link block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white rounded-md transition-colors duration-200">';
        echo '<i class="' . $item['icon'] . ' mr-2"></i>' . $item['title'];
        echo '</a>';
    }
}

// Render menu based on user role
if (isset($menu[$userRole])) {
    echo '<nav class="mt-4 space-y-2">';
    foreach ($menu[$userRole] as $item) {
        renderMenuItem($item);
    }
    
    // Add common menu items for all users
    echo '<div class="border-t my-4"></div>';
    echo '<a href="?module=password" class="sidebar-link block px-4 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white rounded-md transition-colors duration-200">';
    echo '<i class="fas fa-key mr-2"></i>Ubah Password';
    echo '</a>';
    echo '</nav>';
}
?>