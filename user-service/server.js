// server.js
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');

const app = express();
const PORT = process.env.PORT || 4000;

app.use(cors());
app.use(express.json());

const dbConfig = {
  host: process.env.DB_HOST || 'user-db',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || 'root',
  database: process.env.DB_NAME || 'userdb',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
};

let pool;

// Initialize DB pool and ensure table exists
async function initDb() {
  try {
    pool = mysql.createPool(dbConfig);
    // create table if not exists
    const createTableSQL = `
      CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        role VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `;
    await pool.query(createTableSQL);
    console.log('Connected to MySQL and ensured users table exists.');
  } catch (err) {
    console.error('Error initializing DB:', err);
    process.exit(1);
  }
}

// GET /users
app.get('/users', async (req, res) => {
  try {
    const [rows] = await pool.query('SELECT id, name, email, role, created_at FROM users ORDER BY id ASC');
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error fetching users' });
  }
});

// GET /users/:id
app.get('/users/:id', async (req, res) => {
  const id = parseInt(req.params.id);
  if (isNaN(id)) return res.status(400).json({ message: 'Invalid id' });

  try {
    const [rows] = await pool.query('SELECT id, name, email, role, created_at FROM users WHERE id = ?', [id]);
    if (rows.length === 0) return res.status(404).json({ message: 'User not found' });
    res.json(rows[0]);
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: 'Error fetching user' });
  }
});

// POST /users
app.post('/users', async (req, res) => {
  const { name, email, role } = req.body;
  if (!name || !email || !role) return res.status(400).json({ message: 'All fields are required' });

  try {
    const [result] = await pool.query('INSERT INTO users (name, email, role) VALUES (?, ?, ?)', [name, email, role]);
    const newId = result.insertId;
    const [rows] = await pool.query('SELECT id, name, email, role, created_at FROM users WHERE id = ?', [newId]);
    res.status(201).json(rows[0]);
  } catch (err) {
    console.error(err);
    if (err.code === 'ER_DUP_ENTRY') {
      return res.status(409).json({ message: 'Email sudah digunakan' });
    }
    res.status(500).json({ message: 'Error creating user' });
  }
});

// start server after DB init
initDb().then(() => {
  app.listen(PORT, () => {
    console.log(`User Service running on port ${PORT}`);
  });
});