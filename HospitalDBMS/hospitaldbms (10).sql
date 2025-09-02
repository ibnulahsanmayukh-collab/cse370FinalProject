-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2025 at 09:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospitaldbms`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `App_ID` char(10) NOT NULL,
  `Time` time NOT NULL,
  `Date` date NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Upcoming',
  `Doctor_ID` char(10) NOT NULL,
  `Patient_ID` char(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`App_ID`, `Time`, `Date`, `Status`, `Doctor_ID`, `Patient_ID`) VALUES
('A68b1af4d5', '09:00:00', '2025-08-31', 'Complete', 'D000000001', 'P000000002'),
('A68b1fc39c', '10:00:00', '2025-09-05', 'Complete', 'D000000001', 'P000000002'),
('A68b1fcf53', '16:00:00', '2025-09-05', 'Upcoming', 'D000000002', 'P000000002'),
('APP1435115', '09:00:00', '2025-09-02', 'Complete', 'D000000001', 'P000000001'),
('APP3128889', '20:00:00', '2025-09-03', 'Upcoming', 'D000000003', 'P000000001'),
('APP3566299', '13:30:00', '2025-09-03', 'Upcoming', 'D000000001', 'P000000001'),
('APP7028461', '10:30:00', '2025-09-11', 'Complete', 'D000000001', 'P000000001'),
('APP7515594', '20:00:00', '2025-09-03', 'Upcoming', 'D000000002', 'P000000003'),
('APP8015255', '14:00:00', '2025-09-10', 'Complete', 'D000000001', 'P000000001');

-- --------------------------------------------------------

--
-- Table structure for table `app_diag`
--

CREATE TABLE `app_diag` (
  `App_ID` char(10) NOT NULL,
  `Diagnosis` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_diag`
--

INSERT INTO `app_diag` (`App_ID`, `Diagnosis`) VALUES
('A68b1fc39c', 'asd'),
('A68b1fc39c', 'ds'),
('APP1435115', 'asd'),
('APP1435115', 'asdasd'),
('APP7028461', 'asd'),
('APP7028461', 'dsa'),
('APP8015255', '123321'),
('APP8015255', 'asdac');

-- --------------------------------------------------------

--
-- Table structure for table `app_presc`
--

CREATE TABLE `app_presc` (
  `App_ID` char(10) NOT NULL,
  `Prescription` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_presc`
--

INSERT INTO `app_presc` (`App_ID`, `Prescription`) VALUES
('A68b1fc39c', 'asd'),
('APP1435115', 'dssda'),
('APP7028461', 'asd'),
('APP8015255', '123321'),
('APP8015255', 'asdfgasv');

-- --------------------------------------------------------

--
-- Table structure for table `bill`
--

CREATE TABLE `bill` (
  `Bill_ID` char(10) NOT NULL,
  `Status` varchar(50) NOT NULL,
  `App_ID` char(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill`
--

INSERT INTO `bill` (`Bill_ID`, `Status`, `App_ID`) VALUES
('BILL4cc379', 'Paid', 'APP8015255'),
('BILL8e04ac', 'Paid', 'APP8015255'),
('BILL90caf5', 'Paid', 'APP7028461');

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `PID` char(10) NOT NULL,
  `Specialization` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`PID`, `Specialization`) VALUES
('D000000001', 'Cardiologist'),
('D000000002', 'General'),
('D000000003', 'Orthopedics');

-- --------------------------------------------------------

--
-- Table structure for table `doctordegree`
--

CREATE TABLE `doctordegree` (
  `PID` char(10) NOT NULL,
  `Degrees` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctordegree`
--

INSERT INTO `doctordegree` (`PID`, `Degrees`) VALUES
('D000000001', 'FCPS, MBBS'),
('D000000002', 'MBBS'),
('D000000003', 'FCPS'),
('D000000003', 'MBBS');

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_appointments`
-- (See below for the actual view)
--
CREATE TABLE `doctor_appointments` (
`App_ID` char(10)
,`Date` date
,`Time` time
,`PatientName` varchar(50)
,`DoctorID` char(10)
);

-- --------------------------------------------------------

--
-- Table structure for table `hospital`
--

CREATE TABLE `hospital` (
  `HospitalID` char(5) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Plot` varchar(25) NOT NULL,
  `Street` varchar(25) NOT NULL,
  `Area` varchar(25) NOT NULL,
  `email` varchar(100) NOT NULL,
  `Phone` char(14) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital`
--

INSERT INTO `hospital` (`HospitalID`, `Name`, `Plot`, `Street`, `Area`, `email`, `Phone`) VALUES
('H0001', 'United Hospital', 'House 5', 'Road 82', 'Gulshan', 'united@hospital.com', '01777777771'),
('H0002', 'Ibn Sina Medical Hospital', 'Plot 24', 'Road 32', 'Dhanmondi', 'ibn@sina.com', '1234120981');

-- --------------------------------------------------------

--
-- Stand-in structure for view `hospital_staff`
-- (See below for the actual view)
--
CREATE TABLE `hospital_staff` (
`HospitalName` varchar(100)
,`PID` char(10)
,`StaffName` varchar(50)
,`shift` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `Item_ID` char(8) NOT NULL,
  `Item_name` varchar(50) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` int(11) NOT NULL,
  `Expiry_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`Item_ID`, `Item_name`, `Quantity`, `Price`, `Expiry_date`) VALUES
('I0000001', 'Paracetamol(Strip of 10)', 100, 20, '2031-09-10'),
('I0000002', 'paracetamol', 111, 21, '2025-07-09'),
('I0000003', 'paracetamol', 111, 21, '2025-09-03'),
('I0000004', 'paracetamol', 111, 21, '2025-09-03'),
('I0000005', 'paracetamol', 111, 21, '2025-09-03'),
('I0000006', 'paracetamol', 111, 21, '2025-09-03'),
('I0000007', 'histacin', 1111, 11, '2029-11-21');

-- --------------------------------------------------------

--
-- Table structure for table `manages`
--

CREATE TABLE `manages` (
  `PID` char(10) NOT NULL,
  `Item_ID` char(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manages`
--

INSERT INTO `manages` (`PID`, `Item_ID`) VALUES
('A000000001', 'I0000002'),
('A000000001', 'I0000003'),
('A000000001', 'I0000004'),
('A000000001', 'I0000005'),
('A000000001', 'I0000006'),
('A000000001', 'I0000007');

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PID` char(10) NOT NULL,
  `BloodGroup` char(5) NOT NULL,
  `HasInsurance` tinyint(1) NOT NULL
) ;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PID`, `BloodGroup`, `HasInsurance`) VALUES
('P000000001', 'A+', 1),
('P000000002', 'A+', 1),
('P000000003', 'B+', 0);

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE `person` (
  `PID` char(10) NOT NULL,
  `Name` varchar(50) NOT NULL,
  `DateofBirth` date NOT NULL,
  `email` varchar(100) NOT NULL,
  `Phone` char(14) NOT NULL,
  `password` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `person`
--

INSERT INTO `person` (`PID`, `Name`, `DateofBirth`, `email`, `Phone`, `password`) VALUES
('A000000001', 'Ahsan', '2002-02-20', 'a@a.a', '01777777779', '123'),
('D000000001', 'Mayukh', '2002-02-20', 'm@m.m', '01777777778', '123'),
('D000000002', 'Dr. Rezowana', '2025-05-24', 'd@d.id', '01888888888', '123'),
('D000000003', 'Mydul', '1999-01-11', 'mydul@m.com', '01333333333', '123'),
('P000000001', 'Ibnul', '2002-02-20', 'i@i.i', '01777777777', '123'),
('P000000002', 'patient 1', '2025-08-07', 's@s.in', '12312312312', '123'),
('P000000003', 'somename', '2000-07-19', 'some@name.com', '123124345', '123');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `PID` char(10) NOT NULL,
  `shift` varchar(50) NOT NULL,
  `hospital_id` char(5) NOT NULL
) ;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`PID`, `shift`, `hospital_id`) VALUES
('A000000001', 'Morning', 'H0001'),
('D000000001', 'Morning', 'H0001'),
('D000000002', 'Evening', 'H0001'),
('D000000003', 'Evening', 'H0001');

-- --------------------------------------------------------

--
-- Structure for view `doctor_appointments`
--
DROP TABLE IF EXISTS `doctor_appointments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_appointments`  AS SELECT `a`.`App_ID` AS `App_ID`, `a`.`Date` AS `Date`, `a`.`Time` AS `Time`, `p`.`Name` AS `PatientName`, `d`.`PID` AS `DoctorID` FROM (((`appointment` `a` join `patient` `pa` on(`a`.`Patient_ID` = `pa`.`PID`)) join `person` `p` on(`pa`.`PID` = `p`.`PID`)) join `doctor` `d` on(`a`.`Doctor_ID` = `d`.`PID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `hospital_staff`
--
DROP TABLE IF EXISTS `hospital_staff`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `hospital_staff`  AS SELECT `h`.`Name` AS `HospitalName`, `s`.`PID` AS `PID`, `p`.`Name` AS `StaffName`, `s`.`shift` AS `shift` FROM ((`staff` `s` join `hospital` `h` on(`s`.`hospital_id` = `h`.`HospitalID`)) join `person` `p` on(`s`.`PID` = `p`.`PID`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`App_ID`),
  ADD KEY `appontment_ibfk_1` (`Patient_ID`),
  ADD KEY `appointment_doctor` (`Doctor_ID`);

--
-- Indexes for table `app_diag`
--
ALTER TABLE `app_diag`
  ADD PRIMARY KEY (`App_ID`,`Diagnosis`);

--
-- Indexes for table `app_presc`
--
ALTER TABLE `app_presc`
  ADD PRIMARY KEY (`App_ID`,`Prescription`);

--
-- Indexes for table `bill`
--
ALTER TABLE `bill`
  ADD PRIMARY KEY (`Bill_ID`),
  ADD KEY `treatment_cost` (`App_ID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`PID`);

--
-- Indexes for table `doctordegree`
--
ALTER TABLE `doctordegree`
  ADD PRIMARY KEY (`PID`,`Degrees`);

--
-- Indexes for table `hospital`
--
ALTER TABLE `hospital`
  ADD PRIMARY KEY (`HospitalID`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`Item_ID`);

--
-- Indexes for table `manages`
--
ALTER TABLE `manages`
  ADD PRIMARY KEY (`PID`,`Item_ID`),
  ADD KEY `item` (`Item_ID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PID`);

--
-- Indexes for table `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`PID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`PID`),
  ADD KEY `worksin` (`hospital_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_doctor` FOREIGN KEY (`Doctor_ID`) REFERENCES `doctor` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `appontment_patient` FOREIGN KEY (`Patient_ID`) REFERENCES `patient` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `app_diag`
--
ALTER TABLE `app_diag`
  ADD CONSTRAINT `diag_def` FOREIGN KEY (`App_ID`) REFERENCES `appointment` (`App_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `app_presc`
--
ALTER TABLE `app_presc`
  ADD CONSTRAINT `presc_def` FOREIGN KEY (`App_ID`) REFERENCES `appointment` (`App_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bill`
--
ALTER TABLE `bill`
  ADD CONSTRAINT `treatment_cost` FOREIGN KEY (`App_ID`) REFERENCES `appointment` (`App_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `doc_def` FOREIGN KEY (`PID`) REFERENCES `staff` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctordegree`
--
ALTER TABLE `doctordegree`
  ADD CONSTRAINT `degree_def` FOREIGN KEY (`PID`) REFERENCES `doctor` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `manages`
--
ALTER TABLE `manages`
  ADD CONSTRAINT `item` FOREIGN KEY (`Item_ID`) REFERENCES `inventory` (`Item_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `manager` FOREIGN KEY (`PID`) REFERENCES `staff` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `patient_def` FOREIGN KEY (`PID`) REFERENCES `person` (`PID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_def` FOREIGN KEY (`PID`) REFERENCES `person` (`PID`),
  ADD CONSTRAINT `worksin` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`HospitalID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
