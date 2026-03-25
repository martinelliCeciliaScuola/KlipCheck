use Klipcheckdb;
create table utente(
id int primary key AUTO_INCREMENT,
username varchar(30) not null,
password varchar(50) not null,
grado enum("visualizzatore","registrato","admin") not null,
email varchar(50) not null
);
create table film(
id int primary key AUTO_INCREMENT,
titolo varchar(40) not null,
trama text not null,
locandina varchar(100),
trailer  varchar(100),
piattaforme varchar(100),
cast text,
regista varchar(50)
);
create table valutazione(
id int primary key AUTO_INCREMENT,
valore enum("0","0.5","1","1.5","2","2.5","3","3.5","4","4.5",
"5","5.5","6","6.5","7","7.5","8","8.5","9","9.5","10"),
utente_id INT NOT NULL,
  film_id INT NOT NULL,
  FOREIGN KEY (utente_id) REFERENCES utente(id),
  FOREIGN KEY (film_id) REFERENCES film(id)
);
CREATE TABLE recensione (
  id INT PRIMARY KEY AUTO_INCREMENT,
  testo VARCHAR(1020) NOT NULL,
  utente_id INT NOT NULL,
  film_id INT NOT NULL,
  FOREIGN KEY (utente_id) REFERENCES utente(id),
  FOREIGN KEY (film_id) REFERENCES film(id)
);
CREATE TABLE mipiace(
id int primary key AUTO_INCREMENT,
utente_id int not null,
recensione_id int not null,
FOREIGN KEY (utente_id) REFERENCES utente(id),
FOREIGN KEY (recensione_id) REFERENCES recensione(id)
);

INSERT INTO utente (username, password, grado, email) VALUES 
('Federico Cervi', 'password123', 'admin', 'cervifederico1@gmail.com'),
('Luca Castelnovo', 'securePass!', 'registrato', 'castelnovo.luca.21@itisriva.edu.it'),
('Cecilia Martinelli', 'cecilia99', 'visualizzatore', 'martinelli.cecilia@itisriva.edu.it');
INSERT INTO film (titolo, trama, locandina, trailer, piattaforme, cast, regista) VALUES 
('Inception', 'Un ladro che ruba segreti aziendali attraverso luso della tecnologia di condivisione dei sogni.', 'inception.jpg', '://youtube.com', 'Netflix, Prime Video', 'Leonardo DiCaprio, Joseph Gordon-Levitt, Ellen Page', 'Christopher Nolan'),
('Interstellar', 'Un gruppo di esploratori spaziali intraprende una missione per salvare lumanità.', 'interstellar.jpg', '://youtube.com', 'Paramount+', 'Matthew McConaughey, Anne Hathaway, Jessica Chastain', 'Christopher Nolan'),
('The Matrix', 'Un hacker scopre la vera natura della realtà e il suo ruolo nella guerra contro le macchine.', 'matrix.jpg', '://youtube.com', 'Netflix', 'Keanu Reeves, Laurence Fishburne, Carrie-Anne Moss', 'Lana Wachowski, Lilly Wachowski'),
('Titanic', 'Una storia damore tra due giovani di classi sociali diverse a bordo del Titanic.', 'titanic.jpg', '://youtube.com', 'Disney+', 'Leonardo DiCaprio, Kate Winslet', 'James Cameron'),
('The Dark Knight', 'Batman affronta il Joker, un criminale anarchico che semina il caos a Gotham.', 'darkknight.jpg', '://youtube.com', 'Netflix, Prime Video', 'Christian Bale, Heath Ledger, Aaron Eckhart', 'Christopher Nolan'),
('Avatar', 'Un ex marine viene inviato su Pandora e si trova diviso tra due mondi.', 'avatar.jpg', '://youtube.com', 'Disney+', 'Sam Worthington, Zoe Saldana, Sigourney Weaver', 'James Cameron');
INSERT INTO valutazione (valore, utente_id, film_id) VALUES 
('9', 1, 1),   -- Federico Cervi vota Inception
('8.5', 2, 1), -- Luca Castelnovo vota Inception
('10', 1, 2);  -- Federico Cervi vota Interstellar
INSERT INTO recensione (testo, valutazione, utente_id, film_id) VALUES 
('Capolavoro assoluto della fantascienza moderna.', 9, 1, 1),
('Trama complessa ma affascinante, cast stellare.', 8, 2, 1),
('Il mio film preferito di sempre, colonna sonora incredibile.', 10, 1, 2);
INSERT INTO mipiace (utente_id, recensione_id) VALUES
(2, 1), -- Luca mette like alla recensione di Federico su Inception
(3, 1), -- Cecilia mette like alla stessa recensione
(1, 2), -- Federico mette like alla recensione di Luca
(3, 2), -- Cecilia mette like alla recensione di Luca
(2, 3); -- Luca mette like alla recensione su Interstellar
