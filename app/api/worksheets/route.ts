import { NextResponse } from 'next/server';
import { sqlClient } from '@/lib/db';
export const dynamic='force-dynamic';
export async function GET(){try{const sql=sqlClient();const rows=await sql`SELECT id,title,class_level,chapter,description,price_paise,original_price_paise,blob_url,created_at FROM worksheets WHERE is_published=true ORDER BY created_at DESC`;return NextResponse.json(rows);}catch(e){console.error(e);return NextResponse.json({error:'Unable to load worksheets.'},{status:500});}}
