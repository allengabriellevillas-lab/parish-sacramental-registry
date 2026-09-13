USE parish_registry;
-- Development account only: username admin / password password. Change or remove before deployment.
INSERT INTO staff_users (full_name,username,email,password_hash,role) VALUES
('Parish Administrator','admin','admin@example.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','admin');
INSERT INTO persons(first_name,middle_name,last_name,gender,date_of_birth,father_name,mother_maiden_name,spouse_name) VALUES
('Maria','Clara','Santos','Female','1998-03-12','Ramon Santos','Teresa Reyes',NULL),
('Juan','Miguel','Dela Cruz','Male','2000-11-02','Enrico Dela Cruz','Liwayway Ramos',NULL),
('Pedro','Nolasco','Fernandez','Male','1985-01-30','Alfonso Fernandez','Corazon Lim','Josefa Marquez Ramos'),
('Ana','Liza','Reyes','Female','2007-08-09','Danilo Reyes','Marites Ocampo',NULL),
('Roberto','Ignacio','Fernandez','Male','1940-02-17','Alfonso Fernandez','Corazon Lim',NULL),
('Sofia','Amor','Bautista','Female','2021-12-01','Michael Bautista','Grace Villanueva',NULL);
INSERT INTO sacramental_records(person_id,sacrament_type,event_date,book_number,page_number,line_number,minister_name,sponsors,margin_notes,created_by) VALUES
(1,'Baptism','1998-04-19','12','45','3','Rev. Fr. Anthony Cruz','Jose Dela Cruz & Ana Bautista','',1),
(2,'Confirmation','2015-05-20','7','12','8','Most Rev. Bishop Ignacio Villaverde','Marco Villanueva','',1),
(3,'Marriage','2010-06-14','3','88','1','Rev. Fr. Anthony Cruz','Witnesses: Rico Santos & Elena Cruz','Convalidated per diocesan decree, 2011.',1),
(4,'Communion','2014-05-04','9','21','5','Rev. Fr. Benedict Ong','—','',1),
(5,'Death','2019-09-02','2','5','12','Rev. Fr. Benedict Ong','—','Interred at Parish Cemetery, Lot 45.',1),
(6,'Baptism','2022-01-16','14','3','19','Rev. Fr. Benedict Ong','Carlo Reyes & Nina Torres','',1);
