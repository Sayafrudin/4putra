import mysql from 'mysql2/promise';

// Konfigurasi koneksi MySQL ke TiDB Cloud Serverless
// Menggunakan koneksi TCP (mysql2) dengan optimasi untuk serverless
const isTiDB = (process.env.DB_HOST || '').includes('tidbcloud.com');

const pool = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USERNAME || process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_DATABASE || '4putra-project',
    port: parseInt(process.env.DB_PORT || '4000'),
    waitForConnections: true,
    connectionLimit: isTiDB ? 5 : 10,
    queueLimit: 0,
    connectTimeout: 10000,
    // SSL untuk TiDB Cloud
    ...(isTiDB ? {
        ssl: {
            rejectUnauthorized: true,
            ca: process.env.DB_SSL_CA || undefined,
        },
    } : {}),
});

// Helper untuk menjalankan query — mengembalikan array baris
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
