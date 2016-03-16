-- requires: CREATE EXTENSION pgcrypto; 

INSERT INTO oauth_users (username, password, first_name, last_name) VALUES ('testuser', encode(digest('123123', 'sha1'), 'hex'), 'Test', 'User'), ('bgeneto', encode(digest('123123', 'sha1'), 'hex'), 'Bernhard', 'Enders');

INSERT INTO oauth_clients (client_id, client_secret, redirect_uri, grant_types, scope, user_id) VALUES
('webappclient', 'UrLVfB4OtE7MUOQVZ5Bm', 'https://localhost:8080/', 'password authorization_code refresh_token', null, 'bgeneto');