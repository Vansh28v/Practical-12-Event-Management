# Practical 12 — Test Cases

Import `database.sql`, start Apache/MySQL and open the project on localhost.

| ID | Test | Steps | Expected result |
|---|---|---|---|
| TC01 | Initial list | Open the home page | Seeded event cards are displayed |
| TC02 | Add event | Submit all required valid fields, no poster | Event is saved and success message appears |
| TC03 | Add with poster | Upload a valid JPG/PNG/WEBP under 2 MB | Poster is stored under a random filename and shown on the card |
| TC04 | Required validation | Submit with title or date missing | Form displays validation error |
| TC05 | Invalid type | Upload a `.txt` file renamed to `.jpg` | Upload is rejected by MIME/image validation |
| TC06 | Unsupported type | Upload a GIF or PDF | Upload is rejected |
| TC07 | Oversized poster | Upload an image larger than 2 MB | Upload is rejected with a size message |
| TC08 | Edit details | Change title, venue or date and save | Event card shows updated values |
| TC09 | Replace poster | Edit an event and upload a different valid poster | New poster appears; previous poster file is removed |
| TC10 | Remove poster | Tick Remove current poster and save | Poster is removed from the event record and disk |
| TC11 | Delete cancel | Click Delete and choose Cancel | No deletion request is sent |
| TC12 | Delete confirm | Click Delete and confirm | Event and poster are deleted; success message appears |
| TC13 | Category filter | Choose Technology and apply | Only Technology events appear |
| TC14 | Status filter | Choose Closed and apply | Only closed events appear |
| TC15 | Search | Search by a title word or venue | Matching event cards appear |
| TC16 | Pagination | Add enough records or use seeded data and click Next | Next page is displayed with filters preserved |
| TC17 | Audit log | Inspect `event_audit_log` in phpMyAdmin after create/update/delete | Action entries and timestamps are recorded |
| TC18 | CSRF check | Submit a POST form without a valid token | Request is rejected with HTTP 403 |

## Screenshot checklist
- [ ] Event list
- [ ] Add event form
- [ ] Event created successfully
- [ ] Event updated
- [ ] Delete confirmation
- [ ] Event deleted successfully
- [ ] Valid poster shown
- [ ] Invalid type rejected
- [ ] File over 2 MB rejected
- [ ] Category/status filter results
- [ ] Audit log entries
