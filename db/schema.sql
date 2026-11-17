CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE IF NOT EXISTS worksheets (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title VARCHAR(180) NOT NULL,
  class_level VARCHAR(12) NOT NULL CHECK (class_level IN ('VI','VII','VIII','IX','X','XI','XII')),
  subject VARCHAR(80) NOT NULL DEFAULT 'Mathematics',
  chapter VARCHAR(180) NOT NULL,
  description TEXT NOT NULL DEFAULT '',
  price_paise INTEGER NOT NULL DEFAULT 0 CHECK (price_paise >= 0),
  original_price_paise INTEGER CHECK (original_price_paise IS NULL OR original_price_paise >= 0),
  blob_url TEXT NOT NULL,
  blob_pathname TEXT NOT NULL,
  file_size_bytes BIGINT NOT NULL DEFAULT 0,
  is_published BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS worksheets_public_idx ON worksheets(is_published, class_level, created_at DESC);

CREATE TABLE IF NOT EXISTS enquiries (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  student_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  class_level VARCHAR(12) NOT NULL,
  message TEXT NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'new' CHECK (status IN ('new','contacted','closed')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS enquiries_created_idx ON enquiries(created_at DESC);

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value JSONB NOT NULL DEFAULT '{}'::jsonb,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
INSERT INTO site_settings(setting_key, setting_value) VALUES
('brand', '{"name":"Ganit Adda","tagline":"Mathematics Made Simple. Success Made Certain.","phone":"","email":""}'::jsonb)
ON CONFLICT (setting_key) DO NOTHING;
