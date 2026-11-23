INSERT INTO guests (first_name, last_name, age_group, has_parental_consent, alcohol_allowed, guardian_name, qr_code, fallback_code, email, checked_in, checked_in_at, blocked) VALUES
('Alex',   'Example',   'adult', 0, 0, NULL,           'a1b2c3d4e5f60718293a4b5c6d7e8f90', '1024', 'alex@example.com', 1, NOW(), 0),
('Jamie',  'Sample',    'adult', 0, 0, NULL,           'b2c3d4e5f60718293a4b5c6d7e8f90a1', '2048', NULL,               1, NOW(), 0),
('Robin',  'Placeholder','minor', 1, 1, NULL,          'c3d4e5f60718293a4b5c6d7e8f90a1b2', '3072', NULL,               1, NOW(), 0),
('Taylor', 'Demo',      'minor', 0, 0, 'Morgan Demo',  'd4e5f60718293a4b5c6d7e8f90a1b2c3', '4096', NULL,               1, NOW(), 0),
('Casey',  'Test',      'minor', 0, 0, 'Jamie Sample', 'e5f60718293a4b5c6d7e8f90a1b2c3d4', '5120', NULL,               1, NOW(), 0),
('Morgan', 'Demo',      'adult', 0, 0, NULL,           'f60718293a4b5c6d7e8f90a1b2c3d4e5', '6144', NULL,               0, NULL,  0),
('Jordan', 'Mock',      'minor', 0, 1, NULL,           '0718293a4b5c6d7e8f90a1b2c3d4e5f6', '7168', NULL,               0, NULL,  0),
('Riley',  'Fixture',   'adult', 0, 0, NULL,           '18293a4b5c6d7e8f90a1b2c3d4e5f607', '8192', NULL,               0, NULL,  1);
