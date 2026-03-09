-- ============================================
--  ProfileGen — Database Setup
--  Run this in HeidiSQL or phpMyAdmin
-- ============================================

-- 1. Create & use the database
CREATE DATABASE IF NOT EXISTS profilegen
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE profilegen;

-- 2. Create the profiles table
CREATE TABLE IF NOT EXISTS profiles (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    username   VARCHAR(50)  NOT NULL,
    email      VARCHAR(150) NOT NULL,
    headline   VARCHAR(150)          DEFAULT NULL,
    bio        TEXT                  DEFAULT NULL,
    location   VARCHAR(100)          DEFAULT NULL,
    website    VARCHAR(255)          DEFAULT NULL,
    avatar     VARCHAR(255)          DEFAULT NULL,
    github     VARCHAR(100)          DEFAULT NULL,
    twitter    VARCHAR(100)          DEFAULT NULL,
    linkedin   VARCHAR(100)          DEFAULT NULL,
    skills     TEXT                  DEFAULT NULL,
    created_at DATETIME              DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
--  Optional: seed some sample profiles
-- ============================================

INSERT INTO profiles (name, username, email, headline, bio, location, website, github, twitter, linkedin, skills) VALUES
(
  'Juan dela Cruz',
  'juandelacruz',
  'juan@example.com',
  'Full-Stack Developer · Open Source Fan',
  'I build web apps with PHP and Vue. Coffee-powered coder from Manila.',
  'Manila, Philippines',
  'https://juan.dev',
  'juandelacruz',
  'juandlc',
  'juandelacruz',
  'PHP, MySQL, Vue.js, Laravel, HTML, CSS'
),
(
  'Maria Santos',
  'mariasantos',
  'maria@example.com',
  'UI/UX Designer & Frontend Dev',
  'Turning wireframes into pixel-perfect interfaces. I love clean design and good coffee.',
  'Cebu, Philippines',
  'https://mariasantos.design',
  'mariasantos',
  NULL,
  'mariasantos',
  'Figma, HTML, CSS, JavaScript, Tailwind'
),
(
  'Pedro Reyes',
  'pedroreyes',
  'pedro@example.com',
  'Backend Engineer · MySQL Enthusiast',
  'I write clean PHP and optimize slow queries for fun.',
  'Davao, Philippines',
  NULL,
  'pedroreyes',
  'pedror',
  NULL,
  'PHP, MySQL, REST APIs, Linux, Docker'
);