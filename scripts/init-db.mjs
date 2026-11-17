import { readFile } from 'node:fs/promises';
import { neon } from '@neondatabase/serverless';
const connectionString=process.env.DATABASE_URL||process.env.POSTGRES_URL||process.env.NEON_DATABASE_URL||process.env.POSTGRES_URL_NON_POOLING;
if(!connectionString){console.error('Set DATABASE_URL or POSTGRES_URL to your Neon connection string.');process.exit(1)}
const sql=neon(connectionString);const schema=await readFile(new URL('../db/schema.sql',import.meta.url),'utf8');
// Execute statements individually so Neon HTTP driver does not need a multi-command prepared query.
for(const statement of schema.split(';').map(s=>s.trim()).filter(Boolean)){await sql.query(statement)}
console.log('Ganit Adda database schema installed successfully.');
