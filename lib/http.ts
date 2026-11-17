import { NextResponse } from 'next/server';
export function unauthorized() { return NextResponse.json({ error: 'Please sign in as an administrator.' }, { status: 401 }); }
export function serverError(error: unknown) {
  console.error(error);
  return NextResponse.json({ error: error instanceof Error ? error.message : 'Something went wrong.' }, { status: 500 });
}
