-- Initial database setup with sample data

-- Insert default categories
INSERT INTO categories (name, slug) VALUES
('Central Government', 'central'),
('State Government', 'state'),
('Railway', 'railway'),
('Banking', 'banking'),
('Defense', 'defense'),
('PSU', 'psu'),
('Teaching', 'teaching'),
('Police', 'police');

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password_hash, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@sarkarijobs.com', 'admin');

-- Insert sample tags
INSERT INTO tags (name, slug) VALUES
('Latest', 'latest'),
('Urgent', 'urgent'),
('Popular', 'popular'),
('Government', 'government'),
('Exam', 'exam'),
('Notification', 'notification');

-- Insert sample jobs
INSERT INTO jobs (title, slug, organization, location, apply_link, last_date, vacancy_count, educational_qualification, age_limit, category_id, content, status) VALUES
('Staff Selection Commission Combined Graduate Level Examination', 'ssc-cgl-2024', 'Staff Selection Commission', 'All India', 'https://ssc.nic.in', '2024-12-31', 5000, 'Graduate in any discipline', '18-32 years', 1, 'The Staff Selection Commission will conduct the Combined Graduate Level Examination for recruitment to various Group B and Group C posts in Ministries/Departments of Government of India.', 'published'),

('Railway Recruitment Board Assistant Loco Pilot', 'rrb-alp-2024', 'Railway Recruitment Board', 'All India', 'https://rrbcdg.gov.in', '2024-11-30', 2500, '10+2 with ITI or Diploma', '18-28 years', 3, 'Railway Recruitment Board invites applications for the post of Assistant Loco Pilot in Indian Railways.', 'published'),

('State Bank of India Probationary Officer', 'sbi-po-2024', 'State Bank of India', 'All India', 'https://sbi.co.in/careers', '2024-10-15', 1500, 'Graduate in any discipline', '21-30 years', 4, 'State Bank of India invites applications for recruitment of Probationary Officers in Junior Management Grade Scale-I.', 'published');

-- Insert sample results
INSERT INTO results (title, slug, exam_date, download_link, content) VALUES
('SSC CGL Tier-1 Result 2023', 'ssc-cgl-tier1-result-2023', '2023-07-15', 'https://ssc.nic.in/result', 'Staff Selection Commission has declared the result of Combined Graduate Level Examination Tier-1 2023. Candidates can check their result on the official website.'),

('IBPS PO Prelims Result 2023', 'ibps-po-prelims-result-2023', '2023-08-20', 'https://ibps.in/result', 'Institute of Banking Personnel Selection has announced the result of Probationary Officer Preliminary Examination 2023.'),

('Railway Group D Result 2023', 'railway-group-d-result-2023', '2023-09-10', 'https://rrbcdg.gov.in/result', 'Railway Recruitment Board has declared the result of Group D examination 2023. Selected candidates will be called for document verification.');

-- Insert sample admit cards
INSERT INTO admit_cards (title, slug, exam_date, download_link, instructions) VALUES
('SSC CGL Tier-2 Admit Card 2024', 'ssc-cgl-tier2-admit-card-2024', '2024-01-15', 'https://ssc.nic.in/admit', 'Candidates must bring original photo ID proof along with the admit card. Report to the exam center 30 minutes before the exam time.'),

('IBPS Clerk Mains Admit Card 2024', 'ibps-clerk-mains-admit-card-2024', '2024-01-20', 'https://ibps.in/admit', 'Download your admit card and check the exam center details. Carry original documents for verification.'),

('Railway ALP CBT-2 Admit Card 2024', 'railway-alp-cbt2-admit-card-2024', '2024-02-05', 'https://rrbcdg.gov.in/admit', 'Assistant Loco Pilot Computer Based Test Phase-2 admit card is now available for download.');

-- Insert sample syllabus
INSERT INTO syllabi (title, slug, sections) VALUES
('SSC CGL Syllabus 2024', 'ssc-cgl-syllabus-2024', '{"General Intelligence & Reasoning": ["Analogies", "Similarities", "Differences", "Space visualization", "Problem solving", "Analysis", "Judgment", "Decision making"], "General Awareness": ["History", "Geography", "Economics", "General Science", "Current Affairs"], "Quantitative Aptitude": ["Number Systems", "Computation of Whole Numbers", "Decimals and Fractions", "Relationships between numbers", "Fundamental arithmetical operations"], "English Comprehension": ["Vocabulary", "Grammar", "Sentence structure", "Synonyms", "Antonyms", "Sentence completion"]}'),

('IBPS PO Syllabus 2024', 'ibps-po-syllabus-2024', '{"Reasoning Ability": ["Logical Reasoning", "Alphanumeric Series", "Ranking/Direction/Alphabet Test", "Data Sufficiency", "Coded Inequalities"], "Quantitative Aptitude": ["Simplification", "Profit & Loss", "Mixtures & Alligations", "Simple Interest & Compound Interest", "Time & Work"], "English Language": ["Reading Comprehension", "Cloze Test", "Para jumbles", "Miscellaneous", "Fill in the blanks"], "General Awareness": ["Banking Awareness", "Current Affairs", "Static Awareness"], "Computer Aptitude": ["Computer Basics", "Hardware", "Software", "Internet", "MS Office"]}');

-- Insert sample posts
INSERT INTO posts (title, slug, excerpt, content, category_id, status) VALUES
('How to Prepare for SSC CGL 2024', 'how-to-prepare-ssc-cgl-2024', 'Complete preparation strategy for SSC CGL examination with study plan and tips.', 'Staff Selection Commission Combined Graduate Level examination is one of the most competitive exams in India. Here is a comprehensive preparation strategy...', 1, 'published'),

('Banking Exam Preparation Tips', 'banking-exam-preparation-tips', 'Essential tips and strategies for banking examination preparation.', 'Banking sector offers excellent career opportunities. Here are some proven strategies to crack banking exams...', 4, 'published');

-- Insert site settings
INSERT INTO settings (k, v) VALUES
('site_name', 'SarkariJobs Portal'),
('site_description', 'Latest Government Jobs, Results, Admit Cards & Syllabus'),
('contact_email', 'info@sarkarijobs.com'),
('contact_phone', '+91 9876543210'),
('social_facebook', 'https://facebook.com/sarkarijobs'),
('social_twitter', 'https://twitter.com/sarkarijobs'),
('social_telegram', 'https://t.me/sarkarijobs'),
('analytics_code', ''),
('maintenance_mode', '0');
