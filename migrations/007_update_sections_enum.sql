-- Align content_sections.section_type ENUM with admin options used in edit-result.php
-- Adds 'how_to_check' and 'notes' while preserving existing values

-- NOTE: MySQL requires redefining the full ENUM set when altering; ensure all prior values are present
ALTER TABLE content_sections 
  MODIFY COLUMN section_type ENUM(
    'how_to_apply',
    'mode_of_exam',
    'application_note',
    'age_limit_note',
    'vacancy_note',
    'how_to_check',
    'notes',
    'other'
  ) NOT NULL;
