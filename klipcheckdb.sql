CREATE DATABASE IF NOT EXISTS klipcheckdb;
USE klipcheckdb;

CREATE TABLE utente (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  password VARCHAR(50) NOT NULL,
  grado ENUM('visualizzatore','registrato','admin') NOT NULL,
  email VARCHAR(50) NOT NULL
);

CREATE TABLE film (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titolo VARCHAR(40) NOT NULL,
  trama TEXT NOT NULL,
  locandina VARCHAR(500),
  trailer VARCHAR(500),
  piattaforme VARCHAR(100),
  cast TEXT,
  regista VARCHAR(50)
);

CREATE TABLE valutazione (
  id INT AUTO_INCREMENT PRIMARY KEY,
  valore DECIMAL(3,1),
  utente_id INT NOT NULL,
  film_id INT NOT NULL,
  FOREIGN KEY (utente_id) REFERENCES utente(id) ON DELETE CASCADE,
  FOREIGN KEY (film_id) REFERENCES film(id) ON DELETE CASCADE
);


CREATE TABLE recensione (
  id INT AUTO_INCREMENT PRIMARY KEY,
  testo VARCHAR(1020) NOT NULL,
  utente_id INT NOT NULL,
  film_id INT NOT NULL,
  FOREIGN KEY (utente_id) REFERENCES utente(id) ON DELETE CASCADE,
  FOREIGN KEY (film_id) REFERENCES film(id) ON DELETE CASCADE
);


CREATE TABLE mipiace (
  id INT AUTO_INCREMENT PRIMARY KEY,
  utente_id INT NOT NULL,
  recensione_id INT NOT NULL,
  FOREIGN KEY (utente_id) REFERENCES utente(id) ON DELETE CASCADE,
  FOREIGN KEY (recensione_id) REFERENCES recensione(id) ON DELETE CASCADE
);


INSERT INTO utente (username, password, grado, email) VALUES 
('Federico Cervi', 'password123', 'admin', 'cervifederico1@gmail.com'),
('Luca Castelnovo', 'securePass!', 'registrato', 'castelnovo.luca.21@itisriva.edu.it'),
('Cecilia Martinelli', 'cecilia99', 'visualizzatore', 'martinelli.cecilia@itisriva.edu.it');

INSERT INTO film (titolo, trama, locandina, trailer, piattaforme, cast, regista) VALUES 
('Inception', 'Un ladro che ruba segreti aziendali attraverso sogni.', 'inception.jpg', 'https://youtube.com', 'Netflix, Prime Video', 'Leonardo DiCaprio', 'Christopher Nolan'),
('Interstellar', 'Missione nello spazio per salvare l’umanità.', 'interstellar.jpg', 'https://youtube.com', 'Paramount+', 'Matthew McConaughey', 'Christopher Nolan'),
('The Matrix', 'Un hacker scopre la verità sulla realtà.', 'matrix.jpg', 'https://youtube.com', 'Netflix', 'Keanu Reeves', 'Wachowski');


INSERT INTO valutazione (valore, utente_id, film_id) VALUES 
(9, 1, 1),
(8.5, 2, 1),
(10, 1, 2);


INSERT INTO recensione (testo, utente_id, film_id) VALUES 
('Capolavoro assoluto.', 1, 1),
('Molto bello.', 2, 1),
('Film incredibile.', 1, 2);


INSERT INTO mipiace (utente_id, recensione_id) VALUES
(2, 1),
(3, 1),
(1, 2);
