import mysql from 'mysql2/promise';

// Pool dibuat secara lazy agar env vars dari dotenv sudah tersedia
let pool = null;

function getPool() {
    if (!pool) {
        const isTiDB = (process.env.DB_HOST || '').includes('tidbcloud.com');

        pool = mysql.createPool({
            host: process.env.DB_HOST || '127.0.0.1',
            user: process.env.DB_USERNAME || process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_DATABASE || '4putra-project',
            port: parseInt(process.env.DB_PORT || '3306'),
            waitForConnections: true,
            connectionLimit: isTiDB ? 5 : 10,
            queueLimit: 0,
            connectTimeout: 10000,
            ...(isTiDB ? {
                ssl: {
                    rejectUnauthorized: true,
                    ca: process.env.DB_SSL_CA || undefined,
                },
            } : {}),
        });
    }
    return pool;
}

export async function query(sql, params = []) {
    const [rows] = await getPool().execute(sql, params);
    return rows;
}

export async function queryOne(sql, params = []) {
    const rows = await query(sql, params);
    return rows[0] || null;
}

export async function insert(sql, params = []) {
    const [result] = await getPool().execute(sql, params);
    return result.insertId;
}

export async function update(sql, params = []) {
    const [result] = await getPool().execute(sql, params);
    return result.affectedRows;
}

export default { get query() { return query; }, get queryOne() { return queryOne; }, get insert() { return insert; }, get update() { return update; } };
