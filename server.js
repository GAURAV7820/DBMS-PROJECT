const express = require("express");
const mysql = require("mysql2");
const bodyParser = require("body-parser");
const cors = require("cors");
const nodemailer = require("nodemailer");
const cron = require("node-cron");

const app = express();

app.use(express.static(__dirname));
app.use(cors());


app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.json());

console.log(" Starting Node.js server...");

const db = mysql.createConnection({
  host: "127.0.0.1",
  port: 3306,
  user: "root",        
  password: "",         
  database: "event_management"
});

console.log("⏳ Attempting to connect to MySQL...");

db.connect((err) => {
  if (err) {
    console.error(" MySQL connection failed:", err.message);
    process.exit(1);
  } else {
    console.log(" MySQL connected successfully!");
  }
});


app.get("/", (req, res) => {
  console.log("📡 GET / called");
  res.send("🚀 UniVerse Server (Node.js + MySQL + Email) is running!");
});

app.post("/feedback", (req, res) => {
  console.log("📩 POST /feedback triggered");
  const { student_id, event_id, rating, comments } = req.body;

  if (!student_id || !event_id || !rating) {
    return res.status(400).send("Missing required fields");
  }

  const sql =
    "INSERT INTO feedback (student_id, event_id, rating, comments) VALUES (?, ?, ?, ?)";
  db.query(sql, [student_id, event_id, rating, comments], (err) => {
    if (err) {
      console.error(" Feedback insert failed:", err.message);
      return res.status(500).send("Database error");
    }
    console.log(" Feedback inserted successfully!");
    res.send("🎉 Feedback submitted successfully!");
  });
});

app.get("/feedback/:event_id", (req, res) => {
  console.log("GET /feedback/:event_id triggered");
  const { event_id } = req.params;
  const sql = "SELECT * FROM feedback WHERE event_id = ?";
  db.query(sql, [event_id], (err, results) => {
    if (err) {
      console.error(" Feedback fetch failed:", err.message);
      return res.status(500).send("Database fetch error");
    }
    res.json(results);
  });
});


const transporter = nodemailer.createTransport({
  service: "gmail",
  auth: {
    user: "thakurgaurav7820@gmail.com", 
    pass: "dumtljlhphwhasjc"           
  }
});


function sendReminderEmails(daysBefore = 1, callback) {
  console.log(` Checking for events happening in ${daysBefore} day(s)...`);

  const query = `
    SELECT s.email, s.student_name, e.event_name, e.event_date, e.start_time, v.venue_name
    FROM registrations r
    JOIN students s ON r.student_id = s.student_id
    JOIN events e ON r.event_id = e.event_id
    JOIN venues v ON e.venue_id = v.venue_id
    WHERE e.event_date = CURDATE() + INTERVAL ${daysBefore} DAY
  `;

  db.query(query, (err, results) => {
    if (err) {
      console.error(" MySQL query failed:", err);
      if (callback) callback(err);
      return;
    }

    if (!results || results.length === 0) {
      console.log(` No events found for ${daysBefore} day(s) ahead.`);
      if (callback) callback(null, 0);
      return;
    }

    let sentCount = 0;
    results.forEach((row) => {
      const mailOptions = {
        from: '"UniVerse Event Portal" <thakurgaurav7820@gmail.com>',
        to: row.email,
        subject: `📅 Reminder: ${row.event_name} is on ${row.event_date}`,
        html: `
          <p>Hi <strong>${row.student_name}</strong>,</p>
          <p>This is a friendly reminder that your event <strong>${row.event_name}</strong> is happening in ${daysBefore} day(s)!</p>
          <p><b>Date:</b> ${row.event_date}<br>
             <b>Time:</b> ${row.start_time}<br>
             <b>Venue:</b> ${row.venue_name}</p>
          <p>See you there!<br>– The UniVerse Team</p>
        `
      };

      transporter.sendMail(mailOptions, (error) => {
        if (error) {
          console.error(` Failed to send email to ${row.email}:`, error.message);
        } else {
          console.log(` Reminder email sent to ${row.email}`);
          sentCount++;
        }
      });
    });

    if (callback) callback(null, sentCount);
  });
}


cron.schedule("07 09 * * *", () => {
  sendReminderEmails();
});

app.get("/send-test-email", (req, res) => {
  const mailOptions = {
    from: '"UniVerse Event Portal" <thakurgaurav7820@gmail.com>',
    to: "thakurgaurav7820@gmail.com",
    subject: " Test Email from UniVerse Project",
    html: `
      <h3>Hi Gaurav </h3>
      <p>This is a test email from your UniVerse Node.js server.</p>
      <p>If you receive this, your Gmail App Password setup works perfectly! 🎯</p>
    `
  };

  transporter.sendMail(mailOptions, (error) => {
    if (error) {
      console.error("❌ Failed to send test email:", error);
      res.status(500).send("Error: " + error.message);
    } else {
      console.log(" Test email sent successfully!");
      res.send(" Test email sent successfully!");
    }
  });
});

app.get("/send-reminders-now", (req, res) => {
  const days = parseInt(req.query.days || "1", 10); 
  sendReminderEmails(days, (err, count) => {
    if (err) {
      return res.status(500).send(" Error sending reminders.");
    }
    res.send(` Sent ${count} reminder email(s) for events in ${days} day(s).`);
  });
});

const PORT = 3000;
app.listen(PORT, () => {
  console.log(` Server running on http://localhost:${PORT}`);
});
