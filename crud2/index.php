<?php
// Start session and include database connection
require_once 'config.php'; // Make sure this file exists and has correct database connection

// Initialize variables with default values
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1; // Ensure page is at least 1
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;

// Verify session
if (!isset($_SESSION['full_name'])) {
    header("Location: login.php");
    exit();
}

// Build the base query
$query = "SELECT * FROM mahasiswa";
$count_query = "SELECT COUNT(*) as total FROM mahasiswa";

// Add search condition if search term exists
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $query .= " WHERE nim LIKE '%$search_escaped%' 
               OR nama LIKE '%$search_escaped%' 
               OR jurusan LIKE '%$search_escaped%' 
               OR email LIKE '%$search_escaped%'";
    $count_query .= " WHERE nim LIKE '%$search_escaped%' 
                    OR nama LIKE '%$search_escaped%' 
                    OR jurusan LIKE '%$search_escaped%' 
                    OR email LIKE '%$search_escaped%'";
}

// Add pagination
$query .= " LIMIT $limit OFFSET $offset";

// Execute queries
$result = mysqli_query($conn, $query);
$count_result = mysqli_query($conn, $count_query);

// Check if queries were successful
if (!$result || !$count_result) {
    die("Query error: " . mysqli_error($conn));
}

// Get total data and calculate total pages
$total_data_row = mysqli_fetch_assoc($count_result);
$total_data = $total_data_row['total'];
$total_pages = ceil($total_data / $limit);

// Ensure page doesn't exceed total pages
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
    // Re-run query with corrected page
    $query = str_replace("LIMIT $limit OFFSET " . (($page - 1) * $limit), 
                         "LIMIT $limit OFFSET $offset", $query);
    $result = mysqli_query($conn, $query);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - CRUD Lanjutan</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info span {
            color: #666;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .search-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 16px;
        }
        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #5a67d8;
            transform: translateY(-2px);
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .no-photo {
            width: 50px;
            height: 50px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 1px solid #dee2e6;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background-color: #667eea;
            color: white;
        }
        .pagination .current {
            background-color: #667eea;
            color: white;
            border-color: #667eea;
        }
        .stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state img {
            width: 100px;
            height: 100px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .search-section {
                flex-direction: column;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>📚 Data Mahasiswa</h1>
            <div class="user-info">
                <span>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <div class="search-section">
            <form method="GET" style="display: flex; flex: 1; gap: 10px;">
                <input type="text" name="search" class="search-box" 
                       placeholder="Cari berdasarkan NIM, Nama, Jurusan, atau Email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">🔍 Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
            <a href="tambah.php" class="btn btn-success">➕ Tambah Mahasiswa</a>
        </div>
        
        <div class="stats">
            <div>
                <strong>Total Data: <?php echo $total_data; ?></strong>
                <?php if (!empty($search)): ?>
                    <span> | Hasil pencarian untuk: "<em><?php echo htmlspecialchars($search); ?></em>"</span>
                <?php endif; ?>
            </div>
            <div>Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></div>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Jurusan</th>
                    <th>Email</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $offset + 1;
                while($row = mysqli_fetch_assoc($result)):
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td>
                        <?php if (!empty($row['foto']) && file_exists("uploads/mahasiswa/" . $row['foto'])): ?>
                            <img src="uploads/mahasiswa/<?php echo $row['foto']; ?>" alt="Foto" class="photo">
                        <?php else: ?>
                            <div class="no-photo">No Photo</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['nim']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama']); ?></td>
                    <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                    <td>
                        <a href='edit.php?id=<?php echo $row['id']; ?>' class='btn btn-warning'>✏️ Edit</a>
                        <a href='hapus.php?id=<?php echo $row['id']; ?>' class='btn btn-danger' 
                           onclick='return confirm("Yakin ingin menghapus data <?php echo htmlspecialchars(addslashes($row['nama'])); ?>?")'>🗑️ Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1&search=<?php echo urlencode($search); ?>">First</a>
                <a href="?page=<?php echo ($page-1); ?>&search=<?php echo urlencode($search); ?>">Previous</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo ($page+1); ?>&search=<?php echo urlencode($search); ?>">Next</a>
                <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>">Last</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="empty-state">
            <h3>📋 Tidak ada data</h3>
            <p>
                <?php if (!empty($search)): ?>
                    Tidak ditemukan data yang sesuai dengan pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>".
                    <br><a href="index.php">Tampilkan semua data</a>
                <?php else: ?>
                    Belum ada data mahasiswa. <a href="tambah.php">Tambah data pertama</a>
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Close database connection
mysqli_close($conn);
?>