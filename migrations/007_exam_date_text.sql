-- Migration: Change admit_cards.exam_date from DATE to VARCHAR(100) to allow free-form text
ALTER TABLE admit_cards
  MODIFY COLUMN exam_date VARCHAR(100) NULL;
