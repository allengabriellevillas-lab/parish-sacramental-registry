CREATE TABLE parish_settings (
 id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1, parish_name VARCHAR(200) NOT NULL DEFAULT 'Parish of Our Lady of the Assumption',
 diocese_name VARCHAR(200) NOT NULL DEFAULT 'Diocese of San Ildefonso', address VARCHAR(255) NOT NULL DEFAULT '',
 seal_image_path VARCHAR(255) NULL, default_priest_name VARCHAR(200) NOT NULL DEFAULT '', priest_signature_path VARCHAR(255) NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO parish_settings (id) VALUES (1);

CREATE TABLE certificate_templates (
 sacrament_type ENUM('Baptism','Communion','Confirmation','Marriage','Death') PRIMARY KEY, title_text VARCHAR(150) NOT NULL,
 body_template TEXT NOT NULL, footer_note VARCHAR(255) NOT NULL DEFAULT 'Not valid without the parish dry seal.',
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO certificate_templates (sacrament_type,title_text,body_template) VALUES
('Baptism','Certificate of Baptism','This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Baptism in this Parish on {eventDate}, according to the Rites of the Roman Catholic Church.'),
('Communion','Certificate of First Holy Communion','This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of First Holy Communion in this Parish on {eventDate}, according to the Rites of the Roman Catholic Church.'),
('Confirmation','Certificate of Confirmation','This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, received the Sacrament of Confirmation in this Parish on {eventDate}, according to the Rites of the Roman Catholic Church.'),
('Marriage','Certificate of Marriage','This is to certify that {name} and {spouse} were joined in Holy Matrimony according to the rites of the Roman Catholic Church on {eventDate}, as attested by the Sacramental Registers of this Parish.'),
('Death','Certificate of Death','This is to certify that {name}, born on {dob} to {fatherName} and {motherName}, departed this life on {eventDate} and was given ecclesiastical rites according to the Roman Catholic Church.');
