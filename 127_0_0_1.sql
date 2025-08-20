-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 20, 2025 at 04:25 AM
-- Server version: 11.5.2-MariaDB
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_portal_db`
--
CREATE DATABASE IF NOT EXISTS `school_portal_db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `school_portal_db`;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `author` int(11) NOT NULL,
  `date_posted` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author` (`author`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

DROP TABLE IF EXISTS `experiments`;
CREATE TABLE IF NOT EXISTS `experiments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `experiment_number` int(11) NOT NULL,
  `description` text NOT NULL,
  `aim` text NOT NULL,
  `task` text NOT NULL,
  `hint` text DEFAULT NULL,
  `figure_path` varchar(255) DEFAULT NULL,
  `class` varchar(20) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `experiments`
--

INSERT INTO `experiments` (`id`, `title`, `experiment_number`, `description`, `aim`, `task`, `hint`, `figure_path`, `class`, `file_path`, `date_added`) VALUES
(11, 'Basic Formatting in LibreOffice or OpenOffice Writer', 1, 'This activity covers page borders, font styling, color changes, subscripts, superscripts, special characters, and list formatting.', 'To practice and apply basic formatting tools in LO or OO Writer for creating well-presented documents.', 'Apply a page border of size 1. Type \"Order of Size 1\" in red, font size 16 pt.\r\nType: O2 + H2 -> H2O and (x + y)2 = x2 + 2xy + y2 with proper subscript, superscript, and arrow.\r\nWrite 5 lines with a mix of bold, italic, strikethrough, varied font sizes, and styles.\r\nCreate a numbered list and a bulleted list with at least 3 items each, using different styles.', 'Page border: Format, Page Style, Borders, all sides, 1 pt.\r\nSubscript or Superscript: Highlight number, click subscript or superscript button.\r\nArrow: Insert, Special Character.\r\nFont and color changes: Use toolbar.\r\nLists: Use bullet or number icons, right click to change style.', '', '9A', NULL, '2025-08-10 18:15:25'),
(4, 'Data Consolidation Practice', 6, 'To learn Data Consolidation', 'Combine marks from multiple tests (from Fig 6.1) to create a summary of total marks per subject.', 'Open OpenOffice Calc.\r\nConsolidate data from sheets: PT 1, PT 2, and PT 3.\r\nCreate a summary sheet displaying total marks for each subject.', 'Use Data Consolidation', 'uploads/experiments/6_1_1754846468.jpg', '10A', NULL, '2025-08-10 17:21:08'),
(5, 'Subtotal Task', 7, 'To learn how to use sub total', 'Organize and analyse student marks from Fig 7.1 using the subtotal feature feature of LIbre/Open Office Calc.', 'Sort by Student Name\r\nCalculate Total Marks\r\nSum Each Subject\r\nReview Summary Rows', '', 'uploads/experiments/7_1_1754847254.jpg', '10A', NULL, '2025-08-10 17:34:14'),
(6, 'Create Scenarios in Calc', 8, '', 'To create a dynamic profit estimation model in LibreOffice/OpenOffice Calc using scenarios.', 'Base Data: Enter initial values for \"Base Cost,\" \"Selling Price,\" and \"Quantity Sold\" in your spreadsheet.\r\nFormulas: Calculate \"Total Revenue\" (Selling Price * Quantity Sold) and \"Total Profit\" (Total Revenue - (Base Cost * Quantity Sold)).\r\nCreate 3 Scenarios:\r\nHigh Sales: Set a high quantity sold (e.g., 5000 units).\r\nAverage Sales: Use a moderate quantity (e.g., 2500 units).\r\nLow Sales: Assign a low quantity (e.g., 1000 units).\r\nObserve: Switch between scenarios and note how the \"Total Profit\" dynamically changesbased on the quantity sold.', '', '', '10A', NULL, '2025-08-10 17:45:52'),
(7, ' 6 Data Consolidation Practice', 6, 'To learn Data Consolidation', 'Combine marks from multiple tests (from Fig 6.1) to create a summary of total marks per subject.', 'Open OpenOffice Calc.\r\nConsolidate data from sheets: PT 1, PT 2, and PT 3.\r\nCreate a summary sheet displaying total marks for each subject.', 'Use Data Consolidation', 'uploads/experiments/6_1_1754848484.jpg', '10B', NULL, '2025-08-10 17:54:44'),
(8, 'Subtotal Task', 7, 'To learn how to use sub total', 'Organize and analyse student marks from Fig 7.1 using the subtotal feature feature of LIbre/Open Office Calc.', 'Sort by Student Name\r\nCalculate Total Marks\r\nSum Each Subject\r\nReview Summary Rows', '', 'uploads/experiments/7_1_1754848529.jpg', '10B', NULL, '2025-08-10 17:55:29'),
(9, 'Create Scenarios in Calc', 8, '', 'To create a dynamic profit estimation model in LibreOffice/OpenOffice Calc using scenarios.', 'Base Data: Enter initial values for \"Base Cost,\" \"Selling Price,\" and \"Quantity Sold\" in your spreadsheet.\r\nFormulas: Calculate \"Total Revenue\" (Selling Price * Quantity Sold) and \"Total Profit\" (Total Revenue - (Base Cost * Quantity Sold)).\r\nCreate 3 Scenarios:\r\nHigh Sales: Set a high quantity sold (e.g., 5000 units).\r\nAverage Sales: Use a moderate quantity (e.g., 2500 units).\r\nLow Sales: Assign a low quantity (e.g., 1000 units).\r\nObserve: Switch between scenarios and note how the \"Total Profit\" dynamically changesbased on the quantity sold.', '', '', '10B', NULL, '2025-08-10 17:57:14'),
(12, 'Goal Seek', 9, '', 'To find the revised interest rate for a Rs. 50,00,000 loan over 25 years with EMI limited to Rs. 50,000 using LO/OO Calc Goal Seek.\r\n', 'Enter loan details and find interest, sum and EMI.\r\nAssume 12% interest rate intially.\r\nApply Goal Seek to set EMI = Rs. 50,000 by changing the interest rate.\r\nNote the revised interest rate.', '', 'uploads/experiments/9_1_1755500411.png', '10B', NULL, '2025-08-18 07:00:11'),
(13, 'Goal Seek', 9, '', 'To find the revised interest rate for a Rs. 50,00,000 loan over 25 years with EMI limited to Rs. 50,000 using LO/OO Calc Goal Seek.', 'Enter loan details and find interest, sum and EMI.\r\nAssume 12% interest rate intially.\r\nApply Goal Seek to set EMI = Rs. 50,000 by changing the interest rate.\r\nNote the revised interest rate.', '', 'uploads/experiments/9_1_1755588081.png', '10A', NULL, '2025-08-19 07:21:21'),
(14, 'Tables in Writer', 2, '', 'To create a school timetable using a table in LO or OO Writer with modern formatting.', 'Insert a table with suitable rows and columns for a school timetable\r\nAdd headings for days and periods\r\nApply modern formatting: dark background with light text for headers, light background with dark text for data\r\nFill in the timetable and save the document', 'Use the Table menu and select Insert Table to create your table\r\nSelect the header row and use Table Properties or toolbar options to apply a dark background and light text\r\nFor the rest of the table, choose a light background and dark text for better contrast\r\nUse bold and center alignment to improve readability\r\nAdjust column widths to fit the text neatly\r\nSave your document regularly to avoid losing work', '', '9A', NULL, '2025-08-20 04:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `experiment_output_files`
--

DROP TABLE IF EXISTS `experiment_output_files`;
CREATE TABLE IF NOT EXISTS `experiment_output_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `submission_id` (`submission_id`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `experiment_output_files`
--

INSERT INTO `experiment_output_files` (`id`, `submission_id`, `file_path`, `file_name`, `uploaded_at`) VALUES
(1, 3, 'uploads/experiment_outputs/101_3_1754838193_1.svg', 'download.svg', '2025-08-10 15:03:13'),
(2, 3, 'uploads/experiment_outputs/101_3_1754838193_2.png', 'imgbin_d985d132549314d8e879e47383afa94d.png', '2025-08-10 15:03:13'),
(3, 3, 'uploads/experiment_outputs/101_3_1754839145_1.png', 'ChatGPT Image Jul 31, 2025, 12_18_32 PM.png', '2025-08-10 15:19:05'),
(4, 3, 'uploads/experiment_outputs/101_3_1754839145_2.png', 'ChatGPT Image Jul 31, 2025, 11_57_45 AM.png', '2025-08-10 15:19:05'),
(10, 4, 'uploads/experiment_outputs/102_3_1754840545_1.png', 'imgbin_d985d132549314d8e879e47383afa94d.png', '2025-08-10 15:42:25'),
(11, 4, 'uploads/experiment_outputs/102_3_1754840545_2.png', '—Pngtree—beautiful princess crown for a_15867030.png', '2025-08-10 15:42:25'),
(12, 4, 'uploads/experiment_outputs/102_3_1754840545_3.pdf', 'Chapter 4 Class 10.pdf', '2025-08-10 15:42:25'),
(13, 4, 'uploads/experiment_outputs/102_3_1754840545_4.pdf', 'Class10_IT402_Data_Consolidation 1.pdf', '2025-08-10 15:42:25'),
(14, 4, 'uploads/experiment_outputs/102_3_1754840545_5.jpeg', 'IMG_1766.jpeg', '2025-08-10 15:42:25'),
(15, 4, 'uploads/experiment_outputs/102_3_1754840545_6.jpg', 'Screenshot_2021-11-25-02-47-52-049_com.miui.gallery.jpg', '2025-08-10 15:42:25'),
(16, 4, 'uploads/experiment_outputs/102_3_1754840545_7.png', 'a57fd99d-ea17-47a8-aae8-52f94d859671.png', '2025-08-10 15:42:25'),
(17, 4, 'uploads/experiment_outputs/102_3_1754840545_8.png', 'ChatGPT Image Jul 31, 2025, 12_29_02 PM.png', '2025-08-10 15:42:25'),
(18, 4, 'uploads/experiment_outputs/102_3_1754840545_9.png', 'ChatGPT Image Jul 31, 2025, 12_18_32 PM.png', '2025-08-10 15:42:25'),
(19, 4, 'uploads/experiment_outputs/102_3_1754840545_10.png', 'ChatGPT Image Jul 31, 2025, 11_57_45 AM.png', '2025-08-10 15:42:25'),
(20, 4, 'uploads/experiment_outputs/102_3_1754840545_11.png', '20250730_082502_0000.png', '2025-08-10 15:42:25'),
(21, 4, 'uploads/experiment_outputs/102_3_1754840545_12.pdf', 'e-EPIC_GML1428791.pdf', '2025-08-10 15:42:25'),
(22, 4, 'uploads/experiment_outputs/102_3_1754840545_13.jpeg', 'Dark Colour Lehenga for Cocktail Party.jpeg', '2025-08-10 15:42:25'),
(24, 6, 'uploads/experiment_outputs/110_11_1755488108_1.PNG', 'output1.1.PNG', '2025-08-18 03:35:08'),
(26, 8, 'uploads/experiment_outputs/111_11_1755489072_1.PNG', 'Output 1.PNG', '2025-08-18 03:51:12'),
(27, 9, 'uploads/experiment_outputs/113_11_1755489214_1.PNG', 'Output 1.PNG', '2025-08-18 03:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `experiment_submissions`
--

DROP TABLE IF EXISTS `experiment_submissions`;
CREATE TABLE IF NOT EXISTS `experiment_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `experiment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `output_file` varchar(255) NOT NULL,
  `total_files` int(11) DEFAULT 1,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_submission` (`experiment_id`,`student_id`),
  KEY `experiment_id` (`experiment_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `experiment_submissions`
--

INSERT INTO `experiment_submissions` (`id`, `experiment_id`, `student_id`, `output_file`, `total_files`, `submitted_at`, `updated_at`) VALUES
(6, 11, 110, 'multiple_files', 1, '2025-08-18 03:35:08', '2025-08-18 03:35:08'),
(8, 11, 111, 'multiple_files', 1, '2025-08-18 03:51:12', '2025-08-18 03:51:12'),
(9, 11, 113, 'multiple_files', 1, '2025-08-18 03:53:34', '2025-08-18 03:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` char(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `test_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(1, 2, 'What is your sir\'s age?', '32', '33', '34', '35', 'C'),
(2, 3, 'select A', 'A', 'B', 'C', 'D', 'A'),
(3, 3, 'Select B', 'A', 'C', 'B', 'D', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `date_taken` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`id`, `user_id`, `test_id`, `score`, `total_questions`, `date_taken`) VALUES
(1, 101, 2, 0, 1, '2025-08-10 11:58:48'),
(2, 101, 2, 0, 1, '2025-08-10 12:01:13'),
(3, 101, 2, 0, 1, '2025-08-10 12:01:50'),
(4, 101, 2, 1, 1, '2025-08-10 12:01:57'),
(5, 201, 3, 1, 2, '2025-08-10 13:57:58'),
(6, 201, 3, 1, 2, '2025-08-10 14:08:34'),
(7, 101, 2, 1, 1, '2025-08-10 22:46:15');

-- --------------------------------------------------------

--
-- Table structure for table `result_answers`
--

DROP TABLE IF EXISTS `result_answers`;
CREATE TABLE IF NOT EXISTS `result_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` varchar(5) NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `result_id` (`result_id`),
  KEY `question_id` (`question_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `result_answers`
--

INSERT INTO `result_answers` (`id`, `result_id`, `question_id`, `selected_option`, `is_correct`) VALUES
(1, 1, 1, 'D', 0),
(2, 2, 1, 'B', 0),
(3, 3, 1, 'D', 0),
(4, 4, 1, 'C', 1),
(5, 5, 2, 'A', 1),
(6, 5, 3, 'A', 0),
(7, 6, 2, 'A', 1),
(8, 6, 3, 'B', 0),
(9, 7, 1, 'C', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
CREATE TABLE IF NOT EXISTS `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_name` varchar(255) NOT NULL,
  `class` varchar(10) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `test_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `test_name`, `class`, `subject`, `test_date`) VALUES
(2, 'demo test 1', '9A', 'IT', '2025-08-23'),
(3, 'demo 2', '10B', 'IT', '2025-08-11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `class` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=255 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `class`) VALUES
(2, 'admin', '$2y$10$sA0P4M5ew/DKmrFsIq9x6.T9qrLCKfC1UQVZHn.qEwRveKXQQWzHO', 'admin', NULL),
(98, '8B48', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(97, '8B47', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(96, '8B46', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(95, '8B45', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(94, '8B44', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(93, '8B43', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(92, '8B42', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(91, '8B41', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(90, '8B40', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(89, '8B39', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(88, '8B38', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(87, '8B37', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(86, '8B36', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(85, '8B35', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(84, '8B34', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(83, '8B33', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(82, '8B32', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(81, '8B31', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(80, '8B30', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(79, '8B29', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(78, '8B28', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(77, '8B27', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(76, '8B26', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(75, '8B25', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(74, '8B24', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(73, '8B23', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(72, '8B22', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(71, '8B21', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(70, '8B20', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(69, '8B19', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(68, '8B18', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(67, '8B17', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(66, '8B16', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(65, '8B15', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(64, '8B14', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(63, '8B13', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(62, '8B12', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(61, '8B11', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(60, '8B10', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(59, '8B9', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(58, '8B8', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(57, '8B7', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(56, '8B6', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(55, '8B5', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(54, '8B4', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(53, '8B3', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(52, '8B2', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(51, '8B1', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(99, '8B49', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(100, '8B50', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '8B'),
(101, '9A1', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(102, '9A2', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(103, '9A3', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(104, '9A4', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(105, '9A5', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(106, '9A6', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(107, '9A7', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(108, '9A8', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(109, '9A9', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(110, '9A10', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(111, '9A11', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(112, '9A12', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(113, '9A13', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(114, '9A14', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(115, '9A15', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(116, '9A16', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(117, '9A17', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(118, '9A18', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(119, '9A19', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(120, '9A20', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(121, '9A21', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(122, '9A22', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(123, '9A23', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(124, '9A24', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(125, '9A25', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(126, '9A26', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(127, '9A27', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(128, '9A28', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '9A'),
(151, '10A1', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(152, '10A2', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(153, '10A3', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(154, '10A4', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(155, '10A5', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(156, '10A6', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(157, '10A7', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(158, '10A8', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(159, '10A9', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(160, '10A10', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(161, '10A11', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(162, '10A12', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(163, '10A13', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(164, '10A14', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(165, '10A15', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(166, '10A16', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(167, '10A17', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(168, '10A18', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(169, '10A19', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(170, '10A20', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(171, '10A21', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(172, '10A22', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(173, '10A23', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(174, '10A24', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(175, '10A25', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(176, '10A26', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(177, '10A27', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(178, '10A28', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(179, '10A29', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(180, '10A30', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(181, '10A31', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(182, '10A32', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(183, '10A33', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(184, '10A34', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(185, '10A35', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(186, '10A36', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(187, '10A37', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(188, '10A38', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(189, '10A39', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(190, '10A40', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(191, '10A41', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(192, '10A42', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(193, '10A43', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(194, '10A44', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(195, '10A45', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(196, '10A46', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(197, '10A47', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(198, '10A48', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(199, '10A49', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(200, '10A50', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10A'),
(201, '10B1', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(202, '10B2', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(203, '10B3', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(204, '10B4', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(205, '10B5', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(206, '10B6', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(207, '10B7', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(208, '10B8', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(209, '10B9', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(210, '10B10', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(211, '10B11', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(212, '10B12', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(213, '10B13', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(214, '10B14', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(215, '10B15', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(216, '10B16', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(217, '10B17', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(218, '10B18', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(219, '10B19', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(220, '10B20', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(221, '10B21', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(222, '10B22', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(223, '10B23', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(224, '10B24', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(225, '10B25', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(226, '10B26', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(227, '10B27', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(228, '10B28', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(229, '10B29', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(230, '10B30', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(231, '10B31', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(232, '10B32', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(233, '10B33', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(234, '10B34', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(235, '10B35', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(236, '10B36', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(237, '10B37', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(238, '10B38', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(239, '10B39', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(240, '10B40', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(241, '10B41', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(242, '10B42', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(243, '10B43', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(244, '10B44', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(246, '10B46', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(247, '10B47', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(248, '10B48', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(249, '10B49', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(250, '10B50', '$2y$10$XnTJmta7idJIs3IRS/nzzub6rvr0N.VjbJxIHkxkoLzbTYK8kxGFa', 'student', '10B'),
(254, 'sanjoy', '$2y$10$4cmoM6xq50/7Hj7PqoQj1.ss1RNU84ZhBH4GeecKIKqu3goMQTbQ6', 'teacher', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
