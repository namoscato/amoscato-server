CREATE TABLE stream (
  id SERIAL PRIMARY KEY,
  type VARCHAR(20) NOT NULL,
  source_id VARCHAR(40) NOT NULL,
  title VARCHAR(255),
  url TEXT,
  photo_url TEXT DEFAULT NULL,
  photo_width SMALLINT DEFAULT NULL,
  photo_height SMALLINT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL,
  CONSTRAINT stream_type_source_id UNIQUE (type, source_id)
);

CREATE INDEX stream_type ON stream (type);
CREATE INDEX stream_created_at_id ON stream (created_at, id);
