SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS accounts (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username           VARCHAR(50)  NOT NULL,
    password_hash      VARCHAR(255) NOT NULL,
    role               ENUM('admin', 'security') NOT NULL,
    guest_list_only    TINYINT(1)   NOT NULL DEFAULT 0,
    is_developer       TINYINT(1)   NOT NULL DEFAULT 0,
    introduction_done  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_accounts_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guests (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name            VARCHAR(100) NOT NULL,
    last_name             VARCHAR(100) NOT NULL,
    age_group             ENUM('minor', 'adult') NOT NULL,
    has_parental_consent  TINYINT(1)   NOT NULL DEFAULT 0,
    alcohol_allowed       TINYINT(1)   NOT NULL DEFAULT 0,
    guardian_name         VARCHAR(201) NULL,
    qr_code               CHAR(32)     NOT NULL,
    fallback_code         CHAR(4)      NOT NULL,
    email                 VARCHAR(255) NULL,
    last_email_sent_at    DATETIME     NULL,
    checked_in            TINYINT(1)   NOT NULL DEFAULT 0,
    checked_in_at         DATETIME     NULL,
    blocked               TINYINT(1)   NOT NULL DEFAULT 0,
    created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_guests_qr_code (qr_code),
    UNIQUE KEY uq_guests_fallback_code (fallback_code),
    KEY idx_guests_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket        CHAR(64)  NOT NULL,
    attempted_at  DATETIME  NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rate_limits_bucket (bucket, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
