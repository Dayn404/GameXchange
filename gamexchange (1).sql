-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 31, 2025 at 08:58 AM
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
-- Database: `gamexchange`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Role` varchar(50) DEFAULT 'admin',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyableproduct`
--

CREATE TABLE `buyableproduct` (
  `ProductID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyableproduct`
--

INSERT INTO `buyableproduct` (`ProductID`) VALUES
(1),
(2),
(3);

-- --------------------------------------------------------

--
-- Table structure for table `orderproduct`
--

CREATE TABLE `orderproduct` (
  `OrderProductID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderproduct`
--

INSERT INTO `orderproduct` (`OrderProductID`, `OrderID`, `ProductID`, `Quantity`, `Amount`) VALUES
(1, 1, 2, 1, 25.00),
(2, 2, 1, 1, 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `BuyerID` int(11) NOT NULL,
  `PaymentID` int(11) DEFAULT NULL,
  `OrderDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `BuyerID`, `PaymentID`, `OrderDate`) VALUES
(1, 4, 2, '2025-12-31'),
(2, 3, 4, '2025-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Date` date NOT NULL,
  `Status` enum('success','failed','pending') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `UserID`, `Amount`, `Date`, `Status`) VALUES
(1, 2, 40.00, '2025-12-24', 'success'),
(2, 4, 25.00, '2025-12-31', 'success'),
(3, 4, 0.50, '2025-12-31', 'success'),
(4, 3, 30.00, '2025-12-31', 'success'),
(5, 3, 39.00, '2025-12-31', 'success'),
(6, 3, 1.60, '2025-12-31', 'success'),
(7, 3, 45.50, '2025-12-31', 'success');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `SellerID` int(11) NOT NULL,
  `ProductName` varchar(150) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Availability` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `SellerID`, `ProductName`, `Description`, `Price`, `Availability`) VALUES
(1, 1, 'Batman Arkham Knight', 'Batman™: Arkham Knight brings the award-winning Arkham trilogy from Rocksteady Studios to its epic conclusion. Developed exclusively for New-Gen platforms, Batman: Arkham Knight introduces Rocksteady\'s uniquely designed version of the Batmobile.', 30.00, 6),
(2, 1, 'Bloodborne', 'Bloodborne is played from a third-person perspective. Players control a customizable protagonist, and the gameplay is focused on strategic weapons-based combat and exploration. Players battle varied enemies while using items such as trick weapons and firearms, exploring different locations, interacting with non-player characters, and unraveling the city\'s mysteries. Bloodborne began development in 2012 under the working title of Project Beast. Bearing many similarities to FromSoftware\'s Dark Souls series, Bloodborne was inspired by the literary works of authors H. P. Lovecraft and Bram Stoker, as well as the architectural design of real-world locations in countries such as Romania and the Czech Republic.', 25.00, 2),
(3, 1, 'Metal Gear Solid V', 'Ushering in a new era for the METAL GEAR franchise with cutting-edge technology powered by the Fox Engine, METAL GEAR SOLID V: The Phantom Pain, will provide players a first-rate gaming experience as they are offered tactical freedom to carry out open-world missions.', 19.99, 5),
(4, 1, 'Gran Turismo 7', 'Gran Turismo 7 is a 2022 sim racing video game developed by Polyphony Digital and published by Sony Interactive Entertainment. It is the eighth main installment and the thirteenth overall in the Gran Turismo series, following Gran Turismo Sport (2017). The game was released for the PlayStation 4 and PlayStation 5. Gran Turismo 7 features virtual reality (VR) support with PlayStation VR2 through a free in-game update.', 70.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `productimage`
--

CREATE TABLE `productimage` (
  `ImageID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `ImageURL` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productimage`
--

INSERT INTO `productimage` (`ImageID`, `ProductID`, `ImageURL`) VALUES
(1, 1, 'https://wolfsgamingblog.com/wp-content/uploads/2015/06/arkham-knight-shot-02.jpg'),
(2, 2, 'https://www.pluggedin.com/wp-content/uploads/2020/01/Bloodborne-large-1024x587.jpg'),
(3, 3, 'https://m.media-amazon.com/images/M/MV5BNDg0ODE3NmUtOTczYy00NWQwLWEyNDctZGMxYzIwNDY2Y2FlXkEyXkFqcGc@._V1_QL75_UX190_CR0,2,190,281_.jpg'),
(4, 4, 'https://upload.wikimedia.org/wikipedia/en/1/14/Gran_Turismo_7_cover_art.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `productplatform`
--

CREATE TABLE `productplatform` (
  `PlatformID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Platform` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productplatform`
--

INSERT INTO `productplatform` (`PlatformID`, `ProductID`, `Platform`) VALUES
(3, 1, 'PC'),
(1, 1, 'PS5'),
(2, 1, 'XBOX'),
(4, 2, 'PS5'),
(7, 3, 'PC'),
(5, 3, 'PS5'),
(6, 3, 'Xbox'),
(8, 4, 'PS5');

-- --------------------------------------------------------

--
-- Table structure for table `rentableproduct`
--

CREATE TABLE `rentableproduct` (
  `ProductID` int(11) NOT NULL,
  `DailyFee` decimal(10,2) NOT NULL,
  `LateFee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentableproduct`
--

INSERT INTO `rentableproduct` (`ProductID`, `DailyFee`, `LateFee`) VALUES
(1, 0.50, 0.10),
(3, 0.50, 0.10),
(4, 0.40, 0.20);

-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

CREATE TABLE `rental` (
  `RentalID` int(11) NOT NULL,
  `RenterID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `PaymentID` int(11) DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `ReturnDate` date DEFAULT NULL,
  `Paid` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental`
--

INSERT INTO `rental` (`RentalID`, `RenterID`, `ProductID`, `PaymentID`, `StartDate`, `EndDate`, `ReturnDate`, `Paid`) VALUES
(1, 2, 1, 1, '2025-12-23', '2026-03-13', '2025-12-23', 1),
(2, 4, 1, 3, '2025-12-31', '2026-01-01', NULL, 1),
(3, 3, 4, 6, '2025-12-31', '2026-01-04', '2025-12-31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `UserID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Rating` int(11) DEFAULT NULL CHECK (`Rating` between 1 and 5),
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`UserID`, `ProductID`, `Date`, `Rating`, `Comment`) VALUES
(2, 1, '2025-12-24', 5, 'Very Good Game.'),
(3, 4, '2025-12-31', 4, 'Very realistic sim racer');

-- --------------------------------------------------------

--
-- Table structure for table `useraddress`
--

CREATE TABLE `useraddress` (
  `AddressID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraddress`
--

INSERT INTO `useraddress` (`AddressID`, `UserID`, `Address`) VALUES
(1, 3, '3, SE st, South Montana'),
(2, 4, '123, West Arizona');

-- --------------------------------------------------------

--
-- Table structure for table `userphone`
--

CREATE TABLE `userphone` (
  `PhoneID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `userphone`
--

INSERT INTO `userphone` (`PhoneID`, `UserID`, `Phone`) VALUES
(1, 1, '01870899769'),
(2, 1, '01733378508'),
(3, 2, '01733378507'),
(4, 3, '111'),
(5, 3, '123'),
(6, 4, '123');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Name`, `Password`, `Email`, `role`) VALUES
(1, 'Muhtasim Daiyan', '$2y$10$OORObEwBLFD/3IWZuDYLYuKaDp0th5s1PsFyEUn8HQydqQ/AOCLqe', 'zinnah.1133@gmail.com', 'admin'),
(2, 'Rafi', '$2y$10$0izKRLqVwcW3TiwQ94VcJ.f44/FAPb65bR9UPh1YahPKusR6q34AK', 'rafi@gmail.com', 'user'),
(3, 'Tom Hanks', '$2y$10$wEE08guly1.ZOFCR6a5KOubDYwxB6hWXyLz5xTlPPx7l/Gx9aCpmq', 'hank@gmail.com', 'user'),
(4, 'Donald', '$2y$10$8qMCia41p9deHTG4KKUh.uN6T7o8a9SK9hizMOYStFRYDqPU/3sIu', 'donald@gmail.com', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `watchlist`
--

CREATE TABLE `watchlist` (
  `UserID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `DateAdded` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `UX_Admin_User` (`UserID`);

--
-- Indexes for table `buyableproduct`
--
ALTER TABLE `buyableproduct`
  ADD PRIMARY KEY (`ProductID`);

--
-- Indexes for table `orderproduct`
--
ALTER TABLE `orderproduct`
  ADD PRIMARY KEY (`OrderProductID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `BuyerID` (`BuyerID`),
  ADD KEY `PaymentID` (`PaymentID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `SellerID` (`SellerID`);

--
-- Indexes for table `productimage`
--
ALTER TABLE `productimage`
  ADD PRIMARY KEY (`ImageID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `productplatform`
--
ALTER TABLE `productplatform`
  ADD PRIMARY KEY (`PlatformID`),
  ADD UNIQUE KEY `ProductID` (`ProductID`,`Platform`);

--
-- Indexes for table `rentableproduct`
--
ALTER TABLE `rentableproduct`
  ADD PRIMARY KEY (`ProductID`);

--
-- Indexes for table `rental`
--
ALTER TABLE `rental`
  ADD PRIMARY KEY (`RentalID`),
  ADD KEY `RenterID` (`RenterID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `PaymentID` (`PaymentID`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`UserID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `useraddress`
--
ALTER TABLE `useraddress`
  ADD PRIMARY KEY (`AddressID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `userphone`
--
ALTER TABLE `userphone`
  ADD PRIMARY KEY (`PhoneID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD PRIMARY KEY (`UserID`,`ProductID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orderproduct`
--
ALTER TABLE `orderproduct`
  MODIFY `OrderProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `productimage`
--
ALTER TABLE `productimage`
  MODIFY `ImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `productplatform`
--
ALTER TABLE `productplatform`
  MODIFY `PlatformID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rental`
--
ALTER TABLE `rental`
  MODIFY `RentalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `useraddress`
--
ALTER TABLE `useraddress`
  MODIFY `AddressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `userphone`
--
ALTER TABLE `userphone`
  MODIFY `PhoneID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `FK_Admin_User` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `buyableproduct`
--
ALTER TABLE `buyableproduct`
  ADD CONSTRAINT `buyableproduct_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `orderproduct`
--
ALTER TABLE `orderproduct`
  ADD CONSTRAINT `orderproduct_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`),
  ADD CONSTRAINT `orderproduct_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`BuyerID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`PaymentID`) REFERENCES `payment` (`PaymentID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`SellerID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `productimage`
--
ALTER TABLE `productimage`
  ADD CONSTRAINT `productimage_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `productplatform`
--
ALTER TABLE `productplatform`
  ADD CONSTRAINT `productplatform_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `rentableproduct`
--
ALTER TABLE `rentableproduct`
  ADD CONSTRAINT `rentableproduct_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `rental_ibfk_1` FOREIGN KEY (`RenterID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `rental_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `rentableproduct` (`ProductID`),
  ADD CONSTRAINT `rental_ibfk_3` FOREIGN KEY (`PaymentID`) REFERENCES `payment` (`PaymentID`);

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `useraddress`
--
ALTER TABLE `useraddress`
  ADD CONSTRAINT `useraddress_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `userphone`
--
ALTER TABLE `userphone`
  ADD CONSTRAINT `userphone_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `watchlist`
--
ALTER TABLE `watchlist`
  ADD CONSTRAINT `watchlist_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `watchlist_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
