# Repair Notification System

This project is designed to manage repair requests and send notifications to a Discord channel whenever a new repair request is made. 

## Project Structure

```
repair
├── src
│   ├── send_discord_notification.php  # Sends notifications to Discord webhook
│   └── db
│       └── connection.php              # Establishes database connection
├── public
│   └── index.php                       # Entry point for the web application
├── composer.json                       # Composer configuration file
└── README.md                           # Project documentation
```

## Setup Instructions

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd repair
   ```

2. **Install dependencies**:
   Make sure you have Composer installed. Run the following command in the project root:
   ```bash
   composer install
   ```

3. **Configure Database Connection**:
   Update the `src/db/connection.php` file with your database credentials.

4. **Set Up Discord Webhook**:
   Replace the placeholder in `src/send_discord_notification.php` with your actual Discord webhook URL.

## Usage Guidelines

- To create a new repair request, send a request to `public/index.php`. This will handle the incoming data, store it in the database, and trigger a notification to the specified Discord channel.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.