-- 1. Profiles table (user details)
CREATE TABLE profiles (
    id uuid PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    full_name TEXT NULL,
    school_id uuid NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Enable RLS on profiles table
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;

-- Policy: user can only access their own profile
CREATE POLICY "profiles_select_own"
ON profiles
FOR SELECT
USING (auth.uid() = id);

CREATE POLICY "profiles_update_own"
ON profiles
FOR UPDATE
USING (auth.uid() = id);

CREATE POLICY "profiles_insert_own"
ON profiles
FOR INSERT
WITH CHECK (auth.uid() = id);

-- 2. Roles table
CREATE TABLE roles (
    id uuid NOT NULL DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    description TEXT NULL,
    level INTEGER NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    CONSTRAINT roles_pkey PRIMARY KEY (id),
    CONSTRAINT roles_name_key UNIQUE (name)
);

ALTER TABLE roles ENABLE ROW LEVEL SECURITY;

-- Policy: allow all users to read roles
CREATE POLICY "roles_select_all"
ON roles
FOR SELECT
USING (true);

-- 3. Permissions table
CREATE TABLE permissions (
    id uuid NOT NULL DEFAULT gen_random_uuid(),
    code TEXT NOT NULL,
    description TEXT NULL,
    CONSTRAINT permissions_pkey PRIMARY KEY (id),
    CONSTRAINT permissions_code_key UNIQUE (code)
);

ALTER TABLE permissions ENABLE ROW LEVEL SECURITY;

-- Policy: allow all users to read permissions
CREATE POLICY "permissions_select_all"
ON permissions
FOR SELECT
USING (true);

-- 4. User_roles table (links profiles and roles)
CREATE TABLE user_roles (
    user_id uuid NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    role_id uuid NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    assigned_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    CONSTRAINT user_roles_pkey PRIMARY KEY (user_id, role_id)
);

ALTER TABLE user_roles ENABLE ROW LEVEL SECURITY;

-- Policy: user can read only their roles
CREATE POLICY "user_roles_select_own"
ON user_roles
FOR SELECT
USING (auth.uid() = user_id);

-- Policy: allow admin or system roles to assign roles (optional)
-- CREATE POLICY "user_roles_insert_admin"
-- ON user_roles
-- FOR INSERT
-- USING (EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = auth.uid() AND r.level >= 100));
