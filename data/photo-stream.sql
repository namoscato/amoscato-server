CREATE TABLE photo (
  id SERIAL PRIMARY KEY,
  type VARCHAR(20) NOT NULL,
  source_id VARCHAR(40) NOT NULL,
  url TEXT NOT NULL,
  width SMALLINT NOT NULL,
  height SMALLINT NOT NULL,
  title VARCHAR(255),
  reference_url TEXT,
  CONSTRAINT photo_type_source_id UNIQUE (type, source_id)
);

CREATE INDEX photo_type ON photo (type);
