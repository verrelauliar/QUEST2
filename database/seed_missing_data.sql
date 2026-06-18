-- Supplementary Seed Data: Simplified Test Questions
-- Purpose: Clean MCQ-only test data for debugging (5 questions × 20 points = 100 total)
-- Generated: 2025-11-26

DELETE FROM tbl_scores WHERE attempt_id >= 37;
DELETE FROM tbl_answers WHERE attempt_id >= 37;
DELETE FROM tbl_exam_attempts WHERE id_attempt >= 37;
DELETE FROM tbl_questions WHERE exam_id IN (2,3,5,6,8,9,11,12,14,15,17,18,20,21,23,24,25,26,27,28,29,30);

-- Exam 2: Math Mid-Term (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(2,'What is 15 + 27?','multiple',20,'42','52','32','62','A',1),
(2,'What is the square root of 64?','multiple',20,'6','7','8','9','C',2),
(2,'What is 12 × 5?','multiple',20,'50','60','70','80','B',3),
(2,'What is 100 ÷ 4?','multiple',20,'20','25','30','35','B',4),
(2,'What is 3² + 4²?','multiple',20,'25','49','36','64','A',5);

-- Exam 3: Math Final (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(3,'What is x if x + 5 = 12?','multiple',20,'5','6','7','8','C',1),
(3,'What is 20% of 200?','multiple',20,'20','30','40','50','C',2),
(3,'Which number is prime?','multiple',20,'15','21','23','25','C',3),
(3,'Perimeter of square (side 6 cm)?','multiple',20,'12 cm','18 cm','24 cm','36 cm','C',4),
(3,'What is 7 × 8?','multiple',20,'54','56','58','64','B',5);

-- Exam 5: Science Matter (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(5,'Chemical symbol for water?','multiple',20,'O2','H2O','CO2','NaCl','B',1),
(5,'Which state has fixed shape?','multiple',20,'Gas','Liquid','Solid','Plasma','C',2),
(5,'Main energy source for Earth?','multiple',20,'Moon','Sun','Wind','Water','B',3),
(5,'Renewable energy source?','multiple',20,'Coal','Oil','Solar','Gas','C',4),
(5,'Water at 0°C does what?','multiple',20,'Boils','Freezes','Evaporates','Melts','B',5);

-- Exam 6: Science Semester (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(6,'Basic unit of life?','multiple',20,'Atom','Cell','Organ','Tissue','B',1),
(6,'Gas for photosynthesis?','multiple',20,'Oxygen','Nitrogen','CO2','Hydrogen','C',2),
(6,'Red Planet?','multiple',20,'Venus','Mars','Jupiter','Saturn','B',3),
(6,'Largest organ in body?','multiple',20,'Heart','Brain','Skin','Liver','C',4),
(6,'Force pulling objects to Earth?','multiple',20,'Friction','Magnetism','Gravity','Tension','C',5);

-- Exam 8: English Reading (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(8,'Synonym for happy?','multiple',20,'Sad','Joyful','Angry','Tired','B',1),
(8,'Which is a noun?','multiple',20,'Run','Beautiful','Book','Quickly','C',2),
(8,'Plural of child?','multiple',20,'Childs','Children','Childes','Childrens','B',3),
(8,'Correct sentence?','multiple',20,'She go','She goes to school','She going','She gone','B',4),
(8,'Enormous means?','multiple',20,'Very small','Very large','Very fast','Very slow','B',5);

-- Exam 9: English Writing (Grade 7A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(9,'Complete sentence?','multiple',20,'Running fast','The dog barks','Under tree','Very happy','B',1),
(9,'Past tense of eat?','multiple',20,'Eated','Ate','Eaten','Eating','B',2),
(9,'Punctuation for question?','multiple',20,'Period','Comma','Question mark','Exclamation','C',3),
(9,'What is an adjective?','multiple',20,'Action word','Person/place','Describing word','Connecting','C',4),
(9,'Correct spelling?','multiple',20,'Recieve','Recive','Receive','Receeve','C',5);

-- Exam 11: Math Chapter (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(11,'What is 45 - 18?','multiple',20,'25','27','29','31','B',1),
(11,'What is 9 × 7?','multiple',20,'56','63','72','81','B',2),
(11,'Triangle area (base 10, height 6)?','multiple',20,'16','30','60','120','B',3),
(11,'What is 144 ÷ 12?','multiple',20,'10','11','12','13','C',4),
(11,'Sum of triangle angles?','multiple',20,'90°','180°','270°','360°','B',5);

-- Exam 12: Math Practice (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(12,'What is 2³?','multiple',20,'4','6','8','9','C',1),
(12,'What is 50% of 80?','multiple',20,'20','30','40','50','C',2),
(12,'Which equals 0.5?','multiple',20,'1/4','1/2','1/3','2/3','B',3),
(12,'Next in sequence: 2,4,8,16,?','multiple',20,'20','24','28','32','D',4),
(12,'What is 15 × 4?','multiple',20,'50','55','60','65','C',5);

-- Exam 14: Science Living Things (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(14,'What plants produce in photosynthesis?','multiple',20,'CO2','Oxygen','Nitrogen','Hydrogen','B',1),
(14,'NOT a characteristic of living things?','multiple',20,'Growth','Reproduction','Made of metal','Movement','C',2),
(14,'Function of plant roots?','multiple',20,'Make food','Absorb water','Produce O2','Store energy','B',3),
(14,'Which is a mammal?','multiple',20,'Shark','Eagle','Dolphin','Frog','C',4),
(14,'Largest ocean?','multiple',20,'Atlantic','Indian','Arctic','Pacific','D',5);

-- Exam 15: Science Mid-Term (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(15,'Boiling point of water?','multiple',20,'0°C','50°C','100°C','150°C','C',1),
(15,'Element symbol "O"?','multiple',20,'Gold','Oxygen','Iron','Silver','B',2),
(15,'Rock from cooled lava?','multiple',20,'Sedimentary','Metamorphic','Igneous','Limestone','C',3),
(15,'Organ that pumps blood?','multiple',20,'Lungs','Heart','Liver','Kidney','B',4),
(15,'Bones in adult human body?','multiple',20,'106','206','306','406','B',5);

-- Exam 17: English Listening (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(17,'Opposite of hot?','multiple',20,'Warm','Cold','Cool','Freezing','B',1),
(17,'Word that rhymes with cat?','multiple',20,'Dog','Bat','Bird','Fish','B',2),
(17,'Correct morning greeting?','multiple',20,'Good night','Good morning','Good evening','Good afternoon','B',3),
(17,'Pronoun for "Mary and I"?','multiple',20,'They','We','You','She','B',4),
(17,'What is a verb?','multiple',20,'Person','Place','Action','Thing','C',5);

-- Exam 18: English Literature (Grade 7B)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(18,'Main character is called?','multiple',20,'Antagonist','Protagonist','Narrator','Author','B',1),
(18,'What does metaphor mean?','multiple',20,'Direct comparison','Exaggeration','Sound imitation','Question','A',2),
(18,'What is story setting?','multiple',20,'Characters','Time and place','Plot','Theme','B',3),
(18,'Which shows personification?','multiple',20,'Sun is bright','Wind whispered','Tree is tall','Water is cold','B',4),
(18,'What is a simile?','multiple',20,'Uses like/as','Direct comparison','Exaggeration','Contradiction','A',5);

-- Exam 20: Math Functions (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(20,'If f(x)=2x+3, what is f(5)?','multiple',20,'10','11','12','13','D',1),
(20,'Slope of line y=3x-2?','multiple',20,'-2','2','3','-3','C',2),
(20,'Solve: 2x+4=12','multiple',20,'2','3','4','5','C',3),
(20,'Value of x² when x=5?','multiple',20,'10','15','20','25','D',4),
(20,'What is 3x+2x?','multiple',20,'5x','6x','5x²','6x²','A',5);

-- Exam 21: Math Advanced (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(21,'Square root of 144?','multiple',20,'10','11','12','13','C',1),
(21,'Solve: 3(x+2)=15','multiple',20,'1','2','3','4','C',2),
(21,'LCM of 6 and 8?','multiple',20,'12','16','24','48','C',3),
(21,'What is 25% of 160?','multiple',20,'30','35','40','45','C',4),
(21,'Value of π approximately?','multiple',20,'2.14','3.14','4.14','5.14','B',5);

-- Exam 23: Science Chemistry (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(23,'Chemical formula for salt?','multiple',20,'H2O','CO2','NaCl','O2','C',1),
(23,'Metal+oxygen produces?','multiple',20,'Acid','Base','Oxide','Salt','C',2),
(23,'pH level for neutral?','multiple',20,'0','7','14','21','B',3),
(23,'Gas from photosynthesis?','multiple',20,'Nitrogen','Oxygen','CO2','Hydrogen','B',4),
(23,'Atomic number of Carbon?','multiple',20,'4','6','8','12','B',5);

-- Exam 24: Science Comprehensive (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(24,'Powerhouse of cell?','multiple',20,'Nucleus','Mitochondria','Ribosome','Chloroplast','B',1),
(24,'Planet closest to Sun?','multiple',20,'Venus','Earth','Mercury','Mars','C',2),
(24,'Speed of light?','multiple',20,'300,000 km/s','3,000 km/s','30,000 km/s','3M km/s','A',3),
(24,'System regulating temperature?','multiple',20,'Digestive','Nervous','Circulatory','Respiratory','B',4),
(24,'Battery stores what energy?','multiple',20,'Kinetic','Chemical','Thermal','Nuclear','B',5);

-- Exam 25: English Grammar (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(25,'Correct subject-verb agreement?','multiple',20,'They was','They were happy','They is','They be','B',1),
(25,'Past participle of go?','multiple',20,'Went','Gone','Going','Goes','B',2),
(25,'Compound sentence?','multiple',20,'I am happy','I ran fast','I studied and passed','Study hard','C',3),
(25,'What is an adverb?','multiple',20,'Describes noun','Describes verb','Names person','Shows action','B',4),
(25,'Which is a conjunction?','multiple',20,'Beautiful','Quickly','And','Book','C',5);

-- Exam 26: English Essay (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(26,'Thesis statement should?','multiple',20,'Conclude essay','State main idea','Give example','Ask question','B',1),
(26,'Transition showing contrast?','multiple',20,'Also','However','Therefore','Furthermore','B',2),
(26,'What comes first in essay?','multiple',20,'Conclusion','Body','Introduction','References','C',3),
(26,'Most formal sentence?','multiple',20,'Hey there!','Dear Sir/Madam,','What''s up?','Yo!','B',4),
(26,'What is topic sentence?','multiple',20,'Last sentence','First of paragraph','Title','Conclusion','B',5);

-- Exam 27: English Literature (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(27,'What is irony?','multiple',20,'Exaggeration','Opposite of expectation','Comparison','Repetition','B',1),
(27,'What is foreshadowing?','multiple',20,'Hints about future','Past events','Character traits','Setting','A',2),
(27,'Story climax is?','multiple',20,'Beginning','Turning point','End','Introduction','B',3),
(27,'What is allusion?','multiple',20,'Sound effect','Reference','Exaggeration','Comparison','B',4),
(27,'Theme means?','multiple',20,'Setting','Main message','Character','Plot','B',5);

-- Exam 28: Indonesian Basics (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(28,'Arti kata "belajar"?','multiple',20,'To play','To study','To eat','To sleep','B',1),
(28,'Manakah kata baku?','multiple',20,'Nampak','Tampak','Kelihatan','Terlihat','B',2),
(28,'Lawan kata "tinggi"?','multiple',20,'Besar','Rendah','Panjang','Lebar','B',3),
(28,'Kalimat benar?','multiple',20,'Saya pergi sekolah','Saya ke sekolah','Saya di sekolah','Saya dari sekolah','B',4),
(28,'Sinonim "cantik"?','multiple',20,'Jelek','Indah','Besar','Kecil','B',5);

-- Exam 29: Indonesian Reading (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(29,'Apa ide pokok paragraf?','multiple',20,'Judul','Gagasan utama','Kalimat penutup','Contoh','B',1),
(29,'Ide pokok biasa di mana?','multiple',20,'Tengah','Awal/akhir','Setiap kalimat','Judul','B',2),
(29,'Tujuan membaca intensif?','multiple',20,'Menghibur','Memahami detail','Membaca cepat','Menghafal','B',3),
(29,'Apa itu kesimpulan?','multiple',20,'Pembukaan','Ringkasan','Contoh','Judul','B',4),
(29,'Manakah kalimat fakta?','multiple',20,'Hari panas','Air didih 100°C','Film bagus','Orang terbaik','B',5);

-- Exam 30: Indonesian Writing (Grade 8A)
INSERT INTO tbl_questions (exam_id,question_text,question_type,points,option_a,option_b,option_c,option_d,correct_answer,display_order) VALUES
(30,'Paragraf baik harus?','multiple',20,'Banyak kata','Kesatuan ide','Kata sulit','Banyak kalimat','B',1),
(30,'Kapan pakai tanda koma?','multiple',20,'Akhir kalimat','Memisahkan unsur','Awal kalimat','Tidak pernah','B',2),
(30,'Ciri karangan narasi?','multiple',20,'Meyakinkan','Menceritakan','Menjelaskan','Menggambarkan','B',3),
(30,'Imbuhan "me-" menunjukkan?','multiple',20,'Kata benda','Kata kerja aktif','Kata sifat','Kata keterangan','B',4),
(30,'Ejaan benar?','multiple',20,'di baca','dibaca','di-baca','d baca','B',5);

-- Sample Test: Student 8, Exam 2, gets 2/5 correct = 40%
INSERT INTO tbl_exam_attempts (exam_id,student_id,started_at,submitted_at,status) VALUES 
(2,8,'2025-11-26 09:00:00','2025-11-26 09:40:00','graded');

-- Get the auto-generated attempt_id and use it for foreign key references
INSERT INTO tbl_answers (attempt_id,question_id,student_answer,is_correct,points_earned,graded_at,submitted_at) VALUES
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),(SELECT id_question FROM tbl_questions WHERE exam_id=2 AND display_order=1),'A',TRUE,20,'2025-11-26 10:00','2025-11-26 09:10'),
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),(SELECT id_question FROM tbl_questions WHERE exam_id=2 AND display_order=2),'C',TRUE,20,'2025-11-26 10:00','2025-11-26 09:15'),
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),(SELECT id_question FROM tbl_questions WHERE exam_id=2 AND display_order=3),'A',FALSE,0,'2025-11-26 10:00','2025-11-26 09:20'),
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),(SELECT id_question FROM tbl_questions WHERE exam_id=2 AND display_order=4),'A',FALSE,0,'2025-11-26 10:00','2025-11-26 09:25'),
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),(SELECT id_question FROM tbl_questions WHERE exam_id=2 AND display_order=5),'C',FALSE,0,'2025-11-26 10:00','2025-11-26 09:30');

INSERT INTO tbl_scores (attempt_id,total_points,points_earned,percentage,passed,graded_at) VALUES 
(currval(pg_get_serial_sequence('tbl_exam_attempts','id_attempt')),100,40,40.0,FALSE,'2025-11-26 10:00:00');

-- SUCCESS METRICS:
-- All exams now have 5 MCQs worth 20 points each (Total: 100 points)
-- Sample attempt shows 2/5 correct = 40 points = 40% (mathematically correct)
-- No essay questions (removed for simplified debugging)
