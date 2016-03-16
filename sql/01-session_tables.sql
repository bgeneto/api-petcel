-- CodeIgniter session database driver required table
CREATE TABLE "ci_sessions" (
        "id" varchar(40) NOT NULL,
        "ip_address" varchar(45) NOT NULL,
        "timestamp" bigint DEFAULT 0 NOT NULL,
        "data" text DEFAULT '' NOT NULL
);

-- timestamp index
CREATE INDEX "ci_sessions_timestamp" ON "ci_sessions" ("timestamp");

-- when sess_match_ip = FALSE
ALTER TABLE ci_sessions ADD PRIMARY KEY (id);