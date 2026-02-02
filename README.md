# Grade Reports

A **Moodle local plugin** designed to capture and transmit real-time student performance metrics. Grade Reports automatically packages course information, group memberships, and activity grades into structured JSON payloads for export to external monitoring APIs and dashboards.

## 🚀 How It Works

Grade Reports automates the extraction of student data through a background process, ensuring your external dashboards stay synchronized without manual intervention.

### ⚙️ The Data Pipeline

The plugin operates via a multi-stage process triggered by the Moodle cron system:

1. **Scope Identification**: The plugin identifies target courses, groups, and modules based on your specific configurations in the Plugin Admin, Course, and Module settings.

2. **Data Extraction**: A dedicated scheduled task queries the Moodle database to aggregate real-time grade information and enrollment metadata for all learners within the defined scope.

3. **Payload Construction**: The gathered metrics are structured into a standardized JSON schema, ensuring compatibility with external monitoring tools.

4. **Transmission**: The plugin executes an asynchronous POST request to your configured API endpoint, delivering the data payload securely.

### 🛠 Key Features

- **Granular Control**: Define exactly what data to export at the course, module activity and group level.
- **Automated Scheduling**: Leverages Moodle’s native Scheduled Task API for reliable, periodic updates. The schedule can be modified on the admin scheduled tasks page.
- **External Integration**: Designed to bridge the gap between Moodle’s internal gradebook and third-party analytics platforms.

---

## ⚙️ Installation

### Prerequisites

- Administrator access to the Moodle instance.
- Moodle 5.x or later
- PHP 8+
- A running API server the accepts a post request

### Step-by-step Installation

1.  Download the plugin files.
2.  Place the plugin files in the `local/` directory of your Moodle installation.
    The directory name for the plugin files should be `gradereports`:

```
moodle-root
└── local
    └── gradereports
         └── classes/
         └── db/
         └── lang/
         └── README.md
         └── settings.php
         └── version.php
```

**Use the git clone command:**

```
cd <moodle-root>/local
git clone https://github.com/The-DigitalAcademy/gradereports-moodle-plugin gradereports
```

4.  Log in as an administrator to your Moodle site.
5.  Navigate to `Site administration > Notifications`. Moodle will detect the new plugin and prompt you to Install it.
6.  Follow the on-screen instructions to complete the installation.

### Configuration (Site Administration)

After installation, you must configure the autograder service details:

1.  Navigate to `Site Administration > Plugins > Local plugins > Grade Reports`
2.  Configure the following settings:

| Setting              | Description                                                           |
| -------------------- | --------------------------------------------------------------------- |
| **External API url** | The full URL where grade reports will be POSTed to.                   |
| **Course Tag**       | Tag name for courses that will be included in the report.             |
| **Groups**           | Groups that will be included in the report                            |
| **Activity Tag**     | Tag name for assignments/quizzes that will be included in the report. |

3.  Click **Save changes**.

---

## 🧪 Testing and Verification

To ensure the plugin is correctly capturing data and communicating with your API, follow these steps to perform a manual end-to-end test.

**1. Data Preparation**
Before running the task, ensure the "scope" of your data is correctly tagged in Moodle:

- **Course Selection**: Navigate to a course's settings and add the Course Tag (as defined in your Admin settings).

- **Activity Selection**: Navigate to a Quiz or Assignment within that course. In the module settings, add the Activity Tag.
  - Note: Only activities within tagged courses will be processed.

- **User Enrollment**: Enroll participants in the course and assign them to a Group that matches your Admin configuration.

- **Generate Data**: Ensure a student in a tagged group has made a submission or attempt on a tagged activity. Grades must exist for the payload to be generated.

- **API Readiness**: Ensure your destination server is active and ready to accept POST requests at the URL specified in the plugin settings.

**2. Manual Task Execution (CLI)**
Running the task via the terminal allows you to see real-time errors or logs that might be hidden in the web interface.

1. **Open your Terminal** and navigate to your Moodle root directory:

```
cd /path/to/your/moodle
```

2. **Execute the Task**: Use the command below. Replace path/to/php with your specific PHP executable (e.g., `/usr/bin/php`, `C:\xampp\php\php.exe`, or simply `php`).

```
# Note: Use single quotes for the class name to prevent shell escaping issues
path/to/php admin/cli/scheduled_task.php --execute='\local_gradereports\task\send_reports'
```

**3. Success Verification**
Verify the data transmission through the following methods:

- **Moodle UI**: Navigate to `Site administration > Server > Tasks > Scheduled tasks`. Locate Grade Reports and verify the "Last run" timestamp is current.
- **Task Logs**: Click the Logs link next to the task in the Moodle UI to see any mtrace output or PHP errors.
- **API Server**: Check your external API logs to confirm a `POST` request was received with a structured JSON body.

---

## 📦 Payload Structure

The plugin sends an HTTP POST request with a JSON array to the API URL.
|**Key**|**Type**|**Description**
|--|--|--|
|`uid`|String| unique id value.
|`coursename`|String| Name of the activity's course.
|`groupname`|String| Name of the user's group.
|`userid`|String|user's ID.
|`firstname`|String|User's first name.
|`lastname`|String|User's last name.
|`activitytype`| String| Type of activity (`assignment` or `quiz`).
|`activityname`|String|The name of the activity.
|`grade`|String|user's grade out of 100.
|`duedate`|String|The activity's submission deadline (unix timestamp).
|`submissiondate`|String|The user's submission date (unix timestamp)
|`submissionstatus`|String|The submission status. `ontime | pending | late | missed`.

Fields may be `Null` if not applicable

**Example of the payload:**

JSON

```
[
    {
        "uid": "2221179",
        "coursename": "Programming Foundations",
        "groupname": "jozi26",
        "userid": "4",
        "firstname": "jack",
        "lastname": "Mambo",
        "activitytype": "quiz",
        "activityname": "Quiz: Check your knowledge",
        "grade": "80.00",
        "duedate": "1769603100",
        "submissiondate": "1768907803",
        "submissionstatus": "on time"
  }
]
```
