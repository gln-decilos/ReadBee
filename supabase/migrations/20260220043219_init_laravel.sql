-- Password reset tokens
CREATE TABLE password_reset_tokens (
    email TEXT PRIMARY KEY,
    token TEXT NOT NULL,
    created_at TIMESTAMP NULL
);

-- Sessions
CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    user_id uuid REFERENCES auth.users(id),  -- reference auth.users instead of users
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INT
);

ALTER TABLE sessions ENABLE ROW LEVEL SECURITY;

-- Policy: user can only access their own session
CREATE POLICY "sessions_select_own"
ON sessions
FOR SELECT
USING (auth.uid() = user_id);



-- Cache
CREATE TABLE cache (
    key TEXT PRIMARY KEY,
    value TEXT,
    expiration INT
);

ALTER TABLE cache ENABLE ROW LEVEL SECURITY;

-- Policy: allow all reads/writes
CREATE POLICY "cache_rw_all"
ON cache
FOR ALL
USING (true)
WITH CHECK (true);

-- Cache locks
CREATE TABLE cache_locks (
    key TEXT PRIMARY KEY,
    owner TEXT,
    expiration INT
);

ALTER TABLE cache_locks ENABLE ROW LEVEL SECURITY;

CREATE POLICY "cache_locks_rw_all"
ON cache_locks
FOR ALL
USING (true)
WITH CHECK (true);

-- Jobs
CREATE TABLE jobs (
    id BIGSERIAL PRIMARY KEY,
    queue TEXT,
    payload TEXT,
    attempts SMALLINT,
    reserved_at INT,
    available_at INT NOT NULL,
    created_at INT NOT NULL
);

ALTER TABLE jobs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "jobs_rw_all"
ON jobs
FOR ALL
USING (true)
WITH CHECK (true);

-- Job batches
CREATE TABLE job_batches (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    total_jobs INT NOT NULL,
    pending_jobs INT NOT NULL,
    failed_jobs INT NOT NULL,
    failed_job_ids TEXT,
    options TEXT,
    cancelled_at INT,
    created_at INT NOT NULL,
    finished_at INT
);

ALTER TABLE job_batches ENABLE ROW LEVEL SECURITY;

CREATE POLICY "job_batches_rw_all"
ON job_batches
FOR ALL
USING (true)
WITH CHECK (true);

-- Failed jobs
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid TEXT UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT now()
);

ALTER TABLE failed_jobs ENABLE ROW LEVEL SECURITY;

CREATE POLICY "failed_jobs_rw_all"
ON failed_jobs
FOR ALL
USING (true)
WITH CHECK (true);
