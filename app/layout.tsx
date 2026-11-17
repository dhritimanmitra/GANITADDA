import type { Metadata } from 'next';
import './globals.css';
export const metadata: Metadata = { title: 'Ganit Adda | CBSE Mathematics', description: 'Mathematics worksheets, worked solutions and one-to-one learning for Classes VI–XII.' };
export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) { return <html lang="en"><body>{children}</body></html>; }
