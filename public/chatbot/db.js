import mysql from 'mysql2/promise';

// Konfigurasi koneksi MySQL — sesuaikan dengan .env Laravel
const pool = mysql.createPool({
    host: '127.0.0.1',
    user: 'root',
    password: '',
    database: '4putra-project',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
});

// Helper untuk menjalankan query
export async function query(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows;
}

// Helper untuk mengambil satu baris
export async function queryOne(sql, params = []) {
    const rows = await query(sql, params);
    return rows[0] || null;
}

// Helper untuk insert dan mengembalikan insertId
export async function insert(sql, params = []) {
    const [result] = await pool.execute(sql, params);
    return result.insertId;
}

// Helper untuk update dan mengembalikan affectedRows
export async function update(sql, params = []) {
    const [result] = await pool.execute(sql, params);
    return result.affectedRows;
}

export default pool;
