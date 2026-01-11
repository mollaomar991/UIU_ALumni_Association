# Database Tables & User Workflows
## UIU Alumni Association Platform

---

## Slide 1: Title

# Database Tables & User Workflows
## How Each User Type Interacts with Each Table

**Presented by:** [Your Name]  
**Focus:** Practical database usage and data flow

---

## Slide 2: User Types Overview

### Three Main User Types

**1. Students** 🎓
- Current UIU students
- Seeking mentorship and jobs
- Limited access

**2. Alumni** 👔
- Graduated students
- Mentors and job providers
- Full access

**3. Administrators** 🔧
- Platform managers
- Content moderators
- System-wide access

---

## Slide 3: Table 1 - USERS Table

### User Registration & Profile Management

**Student Workflow:**
```
1. Register → Insert into users table
   - role = 'student'
   - status = 'pending'
   - Upload ID card image

2. Admin approves → Update users
   - status = 'active'

3. Update profile → Update users
   - Add bio, phone, location
```

**Alumni Workflow:**
```
1. Register → Insert into users table
   - role = 'alumni'
   - Select batch & department
   - Upload alumni card

2. Add skills → Update users
   - skills = 'Python, Web Development'
   (Used for mentorship matching)
```

**Data Flow:** Registration Form → PHP Validation → INSERT INTO users

---

## Slide 4: Table 2 - POSTS Table

### Content Creation & Feed

**Student Creates Post:**
```sql
INSERT INTO posts (user_id, content, visibility, created_at)
VALUES (123, 'Looking for internship opportunities!', 'public', NOW());
```

**Alumni Creates Post:**
```sql
INSERT INTO posts (user_id, content, image, visibility)
VALUES (456, 'Sharing my experience at Google', 'image.jpg', 'department');
```

**Admin Pins Important Post:**
```sql
UPDATE posts 
SET is_pinned = TRUE 
WHERE id = 789;
```

**Feed Display Query:**
```sql
SELECT p.*, u.name, u.profile_image
FROM posts p
JOIN users u ON p.user_id = u.id
WHERE p.visibility = 'public' OR p.user_id = ?
ORDER BY p.is_pinned DESC, p.created_at DESC;
```

---

## Slide 5: Table 3 - MESSAGES Table

### Private Messaging System

**Student Sends Message to Alumni:**
```sql
-- Step 1: Insert message
INSERT INTO messages (sender_id, receiver_id, message, is_read)
VALUES (123, 456, 'Can you help me with career advice?', FALSE);

-- Step 2: Create notification
INSERT INTO notifications (user_id, type, message)
VALUES (456, 'message', 'New message from John Doe');
```

**Alumni Reads Message:**
```sql
-- Mark as read
UPDATE messages 
SET is_read = TRUE 
WHERE receiver_id = 456 AND sender_id = 123;
```

**Get Conversation:**
```sql
SELECT * FROM messages
WHERE (sender_id = 123 AND receiver_id = 456)
   OR (sender_id = 456 AND receiver_id = 123)
ORDER BY created_at ASC;
```

---

## Slide 6: Table 4 - JOBS Table

### Job Posting & Application

**Alumni Posts Job:**
```sql
-- Step 1: Create job posting
INSERT INTO jobs (title, company, description, posted_by, status)
VALUES ('Software Engineer', 'Google', 'Looking for...', 456, 'pending');
```

**Admin Approves Job:**
```sql
UPDATE jobs 
SET status = 'approved' 
WHERE id = 101;
```

**Student Browses Jobs:**
```sql
SELECT j.*, u.name as posted_by_name
FROM jobs j
JOIN users u ON j.posted_by = u.id
WHERE j.status = 'approved' 
  AND (j.expires_at IS NULL OR j.expires_at > NOW())
ORDER BY j.created_at DESC;
```

**Data Flow:**
```
Alumni → Create Job → Admin Review → Approved → Student Views
```

---

## Slide 7: Table 5 - EVENTS Table

### Event Management

**Admin Creates Event:**
```sql
INSERT INTO events (title, description, event_date, venue, created_by, is_featured)
VALUES ('Alumni Reunion 2024', 'Annual gathering...', '2024-12-31 18:00:00', 
        'UIU Campus', 1, TRUE);
```

**Student/Alumni Views Events:**
```sql
SELECT e.*, 
       (SELECT COUNT(*) FROM event_participants 
        WHERE event_id = e.id AND status = 'going') as going_count,
       (SELECT COUNT(*) FROM event_participants 
        WHERE event_id = e.id AND status = 'interested') as interested_count
FROM events e
WHERE e.event_date > NOW()
ORDER BY e.is_featured DESC, e.event_date ASC;
```

---

## Slide 8: Table 6 - EVENT_PARTICIPANTS Table

### RSVP System

**Student RSVPs to Event:**
```sql
-- Click "Going"
INSERT INTO event_participants (event_id, user_id, status)
VALUES (201, 123, 'going')
ON DUPLICATE KEY UPDATE status = 'going';
```

**Alumni Changes RSVP:**
```sql
-- Change from "Going" to "Interested"
UPDATE event_participants
SET status = 'interested'
WHERE event_id = 201 AND user_id = 456;
```

**Remove RSVP:**
```sql
DELETE FROM event_participants
WHERE event_id = 201 AND user_id = 123;
```

**Admin Views Participants:**
```sql
SELECT u.name, u.email, ep.status
FROM event_participants ep
JOIN users u ON ep.user_id = u.id
WHERE ep.event_id = 201
ORDER BY ep.status, u.name;
```

---

## Slide 9: Table 7 - MENTORSHIP_REQUESTS Table

### Mentorship Program Workflow

**Student Requests Mentor:**
```sql
-- Step 1: Student finds alumni mentor
SELECT * FROM users 
WHERE role = 'alumni' 
  AND skills LIKE '%Python%' 
  AND department = 'CSE';

-- Step 2: Send mentorship request
INSERT INTO mentorship_requests (mentor_id, mentee_id, message, status)
VALUES (456, 123, 'I would love to learn about web development', 'pending');

-- Step 3: Notify alumni
INSERT INTO notifications (user_id, type, message, reference_id)
VALUES (456, 'mentorship', 'New mentorship request from John Doe', 123);
```

**Alumni Accepts Request:**
```sql
UPDATE mentorship_requests
SET status = 'accepted', updated_at = NOW()
WHERE id = 301 AND mentor_id = 456;
```

**Check Connection Status:**
```sql
SELECT status FROM mentorship_requests
WHERE mentor_id = 456 AND mentee_id = 123;
```

---

## Slide 10: Table 8 - FUNDRAISERS Table

### Fundraising Campaign Management

**Admin Creates Campaign:**
```sql
INSERT INTO fundraisers (title, description, goal_amount, created_by, end_date, status)
VALUES ('Scholarship Fund 2024', 'Support needy students...', 500000.00, 
        1, '2024-12-31 23:59:59', 'active');
```

**Display Active Campaigns:**
```sql
SELECT *, 
       (current_amount / goal_amount * 100) as progress_percentage
FROM fundraisers
WHERE status = 'active' AND end_date > NOW()
ORDER BY created_at DESC;
```

**Admin Closes Campaign:**
```sql
UPDATE fundraisers
SET status = 'completed'
WHERE id = 401 AND current_amount >= goal_amount;
```

---

## Slide 11: Table 9 - DONATIONS Table

### Donation Process with SSLCommerz

**Student/Alumni Donates:**
```sql
-- Step 1: Create pending donation
INSERT INTO donations (fundraiser_id, user_id, amount, transaction_id, status)
VALUES (401, 123, 1000.00, 'DON1703681234567', 'Pending');

-- Step 2: User redirected to SSLCommerz gateway
-- (Payment processing happens externally)

-- Step 3: Payment success callback
UPDATE donations
SET status = 'Success', payment_method = 'SSLCommerz (Visa)'
WHERE transaction_id = 'DON1703681234567';

-- Step 4: Update fundraiser amount
UPDATE fundraisers
SET current_amount = current_amount + 1000.00
WHERE id = 401;

-- Step 5: Notify donor
INSERT INTO notifications (user_id, type, message)
VALUES (123, 'donation', 'Your donation of BDT 1,000 was successful!');
```

**Data Flow:**
```
User → Donation Form → payment_init.php → SSLCommerz → 
payment_success.php → Update Database → Confirmation
```

---

## Slide 12: Table 10 - GALLERY Table

### Photo Sharing (Alumni Only)

**Alumni Uploads Photo:**
```sql
-- Step 1: Upload image file to /uploads/
-- Step 2: Insert gallery record
INSERT INTO gallery (user_id, image_url, caption, tags, batch, department, event_type)
VALUES (456, 'photo_12345.jpg', 'Graduation Day 2020!', '#Graduation #CSE', 
        'CSE 192', 'Computer Science & Engineering', 'achievement');
```

**Filter Gallery by Category:**
```sql
SELECT g.*, u.name, u.profile_image
FROM gallery g
JOIN users u ON g.user_id = u.id
WHERE g.event_type = 'reunion'
ORDER BY g.created_at DESC;
```

**Filter by Batch:**
```sql
SELECT * FROM gallery
WHERE batch = 'CSE 192'
ORDER BY likes_count DESC, created_at DESC;
```

---

## Slide 13: Table 11 - GALLERY_LIKES Table

### Photo Like System

**Alumni Likes Photo:**
```sql
-- Step 1: Check if already liked
SELECT id FROM gallery_likes
WHERE gallery_id = 501 AND user_id = 456;

-- Step 2: If not liked, insert
INSERT INTO gallery_likes (gallery_id, user_id)
VALUES (501, 456);

-- Step 3: Increment like count
UPDATE gallery
SET likes_count = likes_count + 1
WHERE id = 501;
```

**Alumni Unlikes Photo:**
```sql
-- Step 1: Delete like record
DELETE FROM gallery_likes
WHERE gallery_id = 501 AND user_id = 456;

-- Step 2: Decrement like count
UPDATE gallery
SET likes_count = likes_count - 1
WHERE id = 501;
```

**Get User's Liked Photos:**
```sql
SELECT g.* FROM gallery g
JOIN gallery_likes gl ON g.id = gl.gallery_id
WHERE gl.user_id = 456
ORDER BY gl.created_at DESC;
```

---

## Slide 14: Table 12 - NOTIFICATIONS Table

### Notification System

**System Creates Notifications:**

**1. New Message:**
```sql
INSERT INTO notifications (user_id, type, reference_id, message)
VALUES (456, 'message', 123, 'New message from John Doe');
```

**2. Mentorship Request:**
```sql
INSERT INTO notifications (user_id, type, reference_id, message)
VALUES (456, 'mentorship', 301, 'New mentorship request from John Doe');
```

**3. Donation Success:**
```sql
INSERT INTO notifications (user_id, type, reference_id, message)
VALUES (123, 'donation', 601, 'Your donation of BDT 1,000 was successful!');
```

**User Views Notifications:**
```sql
SELECT * FROM notifications
WHERE user_id = 456 AND is_read = FALSE
ORDER BY created_at DESC
LIMIT 10;
```

**Mark as Read:**
```sql
UPDATE notifications
SET is_read = TRUE
WHERE user_id = 456;
```

---

## Slide 15: Table 13 - ADMIN Table

### Admin Management

**Super Admin Creates Moderator:**
```sql
INSERT INTO admin (username, email, password, role)
VALUES ('moderator1', 'mod@uiu.ac.bd', 
        '$2y$10$...hashed_password...', 'moderator');
```

**Admin Login:**
```sql
SELECT * FROM admin
WHERE username = 'admin' OR email = 'admin@uiu.ac.bd';
-- Verify password with PHP password_verify()
```

**Admin Actions Tracked:**
- Created events → `events.created_by`
- Created fundraisers → `fundraisers.created_by`
- Approved jobs → `jobs.status` updates
- Approved users → `users.status` updates

---

## Slide 16: Complete User Journey - Student

### Student's Database Interaction Flow

**1. Registration:**
```sql
INSERT INTO users (...) VALUES (...); -- status='pending'
```

**2. Admin Approval:**
```sql
UPDATE users SET status='active' WHERE id=123;
```

**3. Browse Mentors:**
```sql
SELECT * FROM users WHERE role='alumni' AND skills LIKE '%Python%';
```

**4. Request Mentorship:**
```sql
INSERT INTO mentorship_requests (...) VALUES (...);
```

**5. RSVP to Event:**
```sql
INSERT INTO event_participants (...) VALUES (...);
```

**6. Donate to Campaign:**
```sql
INSERT INTO donations (...) VALUES (...);
UPDATE fundraisers SET current_amount = current_amount + 1000;
```

**7. Send Message:**
```sql
INSERT INTO messages (...) VALUES (...);
```

---

## Slide 17: Complete User Journey - Alumni

### Alumni's Database Interaction Flow

**1. Registration:**
```sql
INSERT INTO users (role='alumni', batch='CSE 192', ...) VALUES (...);
```

**2. Add Skills:**
```sql
UPDATE users SET skills='Python, Web Dev, Career Counseling' WHERE id=456;
```

**3. Post Job:**
```sql
INSERT INTO jobs (...) VALUES (...); -- status='pending'
```

**4. Accept Mentorship Request:**
```sql
UPDATE mentorship_requests SET status='accepted' WHERE id=301;
```

**5. Upload to Gallery:**
```sql
INSERT INTO gallery (...) VALUES (...);
```

**6. Like Photos:**
```sql
INSERT INTO gallery_likes (...) VALUES (...);
UPDATE gallery SET likes_count = likes_count + 1;
```

**7. Donate:**
```sql
INSERT INTO donations (...) VALUES (...);
```

---

## Slide 18: Complete User Journey - Admin

### Admin's Database Interaction Flow

**1. Approve Pending Users:**
```sql
UPDATE users SET status='active' WHERE status='pending';
```

**2. Create Event:**
```sql
INSERT INTO events (created_by=1, ...) VALUES (...);
```

**3. Approve Job Posting:**
```sql
UPDATE jobs SET status='approved' WHERE id=101;
```

**4. Create Fundraiser:**
```sql
INSERT INTO fundraisers (created_by=1, ...) VALUES (...);
```

**5. Moderate Content:**
```sql
DELETE FROM posts WHERE id=789; -- Inappropriate content
```

**6. View Statistics:**
```sql
SELECT COUNT(*) FROM users WHERE status='active';
SELECT COUNT(*) FROM donations WHERE status='Success';
SELECT SUM(amount) FROM donations WHERE status='Success';
```

---

## Slide 19: Data Relationships in Action

### Example: Mentorship Connection

**Step-by-Step Data Flow:**

```
1. Student searches mentors:
   Query: users table (role='alumni', skills LIKE '%Python%')

2. Student sends request:
   Insert: mentorship_requests table
   Insert: notifications table (notify mentor)

3. Alumni receives notification:
   Query: notifications table (user_id=mentor_id, is_read=FALSE)

4. Alumni accepts:
   Update: mentorship_requests (status='accepted')
   Insert: notifications table (notify student)

5. Direct messaging enabled:
   Insert: messages table (sender_id, receiver_id, message)
   Insert: notifications table (notify receiver)

Tables Involved: users, mentorship_requests, notifications, messages
```

---

## Slide 20: Data Relationships - Donation Flow

### Example: Complete Donation Process

**Tables Involved:**

```
1. fundraisers - Campaign details
2. donations - Transaction records
3. users - Donor information
4. notifications - Confirmation alerts

Flow:
Admin creates fundraiser → fundraisers table
User views campaigns → SELECT from fundraisers
User donates → INSERT into donations (status='Pending')
Payment gateway → UPDATE donations (status='Success')
Update campaign → UPDATE fundraisers (current_amount += amount)
Notify user → INSERT into notifications
```

**SQL Sequence:**
```sql
-- 1. Get campaign
SELECT * FROM fundraisers WHERE id=401;

-- 2. Create donation
INSERT INTO donations (...) VALUES (...);

-- 3. Update campaign
UPDATE fundraisers SET current_amount = current_amount + 1000 WHERE id=401;

-- 4. Notify
INSERT INTO notifications (...) VALUES (...);
```

---

## Slide 21: Data Integrity Examples

### How Foreign Keys Protect Data

**Example 1: User Deletion**
```sql
DELETE FROM users WHERE id=123;

-- CASCADE DELETE automatically removes:
- All posts by user (posts.user_id = 123)
- All messages (sender_id or receiver_id = 123)
- All mentorship requests
- All donations
- All gallery photos
- All notifications
```

**Example 2: Event Deletion**
```sql
DELETE FROM events WHERE id=201;

-- CASCADE DELETE automatically removes:
- All event participants (event_participants.event_id = 201)
```

**Example 3: Duplicate Prevention**
```sql
-- Try to RSVP twice to same event
INSERT INTO event_participants (event_id, user_id, status)
VALUES (201, 123, 'going');

-- UNIQUE constraint prevents duplicate
-- Error: Duplicate entry '201-123' for key 'unique_participation'
```

---

## Slide 22: Performance Optimization in Practice

### Real Query Examples

**Slow Query (No Index):**
```sql
SELECT * FROM posts 
WHERE user_id = 123; 
-- Full table scan
```

**Fast Query (With Index):**
```sql
SELECT * FROM posts 
WHERE user_id = 123;
-- Uses idx_user_id index
-- 100x faster
```

**Optimized Join:**
```sql
SELECT p.*, u.name 
FROM posts p
JOIN users u ON p.user_id = u.id
WHERE p.visibility = 'public'
ORDER BY p.created_at DESC
LIMIT 20;
-- Uses indexes on user_id, visibility, created_at
```

**Counting Efficiently:**
```sql
-- Bad: SELECT COUNT(*) FROM posts WHERE user_id = 123;
-- Good: Use denormalized counter or cache result
```

---

## Slide 23: Transaction Example

### Donation Transaction (ACID)

```sql
START TRANSACTION;

-- 1. Create donation record
INSERT INTO donations (fundraiser_id, user_id, amount, transaction_id, status)
VALUES (401, 123, 1000.00, 'TRX123', 'Success');

-- 2. Update fundraiser amount
UPDATE fundraisers 
SET current_amount = current_amount + 1000.00
WHERE id = 401;

-- 3. Create notification
INSERT INTO notifications (user_id, type, message)
VALUES (123, 'donation', 'Donation successful!');

-- If all succeed:
COMMIT;

-- If any fails:
ROLLBACK;
```

**Benefits:**
- All-or-nothing execution
- Data consistency guaranteed
- Prevents partial updates

---

## Slide 24: Common Queries by User Type

### Student Queries

```sql
-- 1. Find mentors
SELECT * FROM users 
WHERE role='alumni' AND department='CSE' AND skills IS NOT NULL;

-- 2. Browse jobs
SELECT * FROM jobs WHERE status='approved' AND expires_at > NOW();

-- 3. View events
SELECT * FROM events WHERE event_date > NOW();

-- 4. Check mentorship status
SELECT status FROM mentorship_requests 
WHERE mentee_id=123 AND mentor_id=456;

-- 5. View notifications
SELECT * FROM notifications WHERE user_id=123 AND is_read=FALSE;
```

---

## Slide 25: Common Queries by User Type

### Alumni Queries

```sql
-- 1. View incoming mentorship requests
SELECT mr.*, u.name, u.email 
FROM mentorship_requests mr
JOIN users u ON mr.mentee_id = u.id
WHERE mr.mentor_id=456 AND mr.status='pending';

-- 2. My posted jobs
SELECT * FROM jobs WHERE posted_by=456 ORDER BY created_at DESC;

-- 3. My gallery photos
SELECT * FROM gallery WHERE user_id=456 ORDER BY likes_count DESC;

-- 4. Photos I liked
SELECT g.* FROM gallery g
JOIN gallery_likes gl ON g.id = gl.gallery_id
WHERE gl.user_id=456;

-- 5. My donations
SELECT d.*, f.title FROM donations d
JOIN fundraisers f ON d.fundraiser_id = f.id
WHERE d.user_id=456 ORDER BY d.created_at DESC;
```

---

## Slide 26: Common Queries by User Type

### Admin Queries

```sql
-- 1. Pending user approvals
SELECT * FROM users WHERE status='pending' ORDER BY created_at;

-- 2. Pending job approvals
SELECT j.*, u.name FROM jobs j
JOIN users u ON j.posted_by = u.id
WHERE j.status='pending';

-- 3. Platform statistics
SELECT 
  (SELECT COUNT(*) FROM users WHERE status='active') as active_users,
  (SELECT COUNT(*) FROM posts) as total_posts,
  (SELECT COUNT(*) FROM events WHERE event_date > NOW()) as upcoming_events,
  (SELECT SUM(current_amount) FROM fundraisers) as total_raised;

-- 4. Event participants
SELECT e.title, COUNT(ep.id) as participants
FROM events e
LEFT JOIN event_participants ep ON e.id = ep.event_id
GROUP BY e.id;

-- 5. Top donors
SELECT u.name, SUM(d.amount) as total_donated
FROM donations d
JOIN users u ON d.user_id = u.id
WHERE d.status='Success'
GROUP BY u.id
ORDER BY total_donated DESC
LIMIT 10;
```

---

## Slide 27: Data Validation in Practice

### How Database Enforces Rules

**1. Role Validation:**
```sql
-- ENUM ensures only valid roles
role ENUM('student', 'alumni')
-- Trying to insert 'teacher' will fail
```

**2. Email Uniqueness:**
```sql
-- UNIQUE constraint prevents duplicate emails
email VARCHAR(255) UNIQUE NOT NULL
```

**3. Required Fields:**
```sql
-- NOT NULL ensures data completeness
name VARCHAR(255) NOT NULL
password VARCHAR(255) NOT NULL
```

**4. Referential Integrity:**
```sql
-- Foreign key ensures valid references
FOREIGN KEY (user_id) REFERENCES users(id)
-- Cannot insert post with non-existent user_id
```

**5. Date Validation:**
```sql
-- Check constraint (via application logic)
WHERE event_date > NOW()
```

---

## Slide 28: Reporting & Analytics

### Database Queries for Insights

**User Growth:**
```sql
SELECT DATE(created_at) as date, COUNT(*) as new_users
FROM users
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date;
```

**Engagement Metrics:**
```sql
SELECT 
  u.name,
  (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
  (SELECT COUNT(*) FROM messages WHERE sender_id = u.id) as messages_sent,
  (SELECT COUNT(*) FROM gallery WHERE user_id = u.id) as photos_uploaded
FROM users u
WHERE u.role = 'alumni'
ORDER BY posts_count DESC
LIMIT 10;
```

**Fundraising Performance:**
```sql
SELECT 
  title,
  goal_amount,
  current_amount,
  (current_amount / goal_amount * 100) as progress,
  (SELECT COUNT(*) FROM donations WHERE fundraiser_id = f.id) as donor_count
FROM fundraisers f
WHERE status = 'active';
```

---

## Slide 29: Backup & Data Safety

### How User Data is Protected

**1. Regular Backups:**
```bash
# Daily backup
mysqldump -u root -p alumni_db > backup_2024_12_27.sql
```

**2. Transaction Logs:**
- All changes logged
- Point-in-time recovery possible

**3. Replication:**
- Master-slave setup
- Real-time data copying

**4. User Data Retention:**
```sql
-- Soft delete (keep data)
UPDATE users SET status='blocked' WHERE id=123;

-- Hard delete (remove data + cascades)
DELETE FROM users WHERE id=123;
```

**5. GDPR Compliance:**
```sql
-- Export user data
SELECT * FROM users WHERE id=123;
SELECT * FROM posts WHERE user_id=123;
-- ... all related data

-- Delete all user data
DELETE FROM users WHERE id=123; -- Cascades to all related tables
```

---

## Slide 30: Summary

### Database Usage by Numbers

**13 Tables Supporting:**
- 3 user types (Student, Alumni, Admin)
- 8 major features
- 20+ foreign key relationships
- 35+ indexes for performance

**Daily Operations:**
- User registrations → users table
- Content creation → posts table
- Messaging → messages table
- Job postings → jobs table
- Event RSVPs → event_participants table
- Mentorship → mentorship_requests table
- Donations → donations + fundraisers tables
- Photo sharing → gallery + gallery_likes tables
- Notifications → notifications table

**Result:** Robust, scalable, and efficient data management

---

## Slide 31: Q&A

# Questions?

**Key Takeaways:**
✅ Each table serves specific user needs  
✅ Foreign keys ensure data integrity  
✅ Indexes optimize query performance  
✅ Transactions maintain consistency  
✅ Proper design supports scalability  

**Thank you!**

---
