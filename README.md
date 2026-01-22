# Grade Reports

A **Moodle local plugin** designed to capture and transmit real-time student performance metrics. Grade Reports automatically packages course information, group memberships, and activity grades into structured JSON payloads for export to external monitoring APIs and dashboards.

## 🚀 What It Does

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

## 🧪 Testing and Debugging

To verify the plugin is sending the data correctly, you must enable **Developer Debugging Mode**.

### Activating Developer Debugging

1.  Navigate to **Site administration** $\to$ **Development** $\to$ **Debugging**.
2.  Under **Debug messages**, select the option:
    - **DEVELOPER: extra Moodle debugging messages for developers.**

3.  Check the box for **Display debug messages**.
4.  Click **Save changes**.

This will show detailed error messages if the plugin encounters issues.

### Testing the Plugin

1. Navigate to a course's settings and add the course tag to it. This tag will ensure that this course is included in the report.
2. Navigate to any quiz or assignment module's settings within the course and add the activity tag to them. This tag will ensure that this activity is included in the report.
3. Add participants to the course and add them to any of the groups selected in the admin settings (`Groups`).
4. Ensure that a participant within any of the selected groups makes a submission or an attempt on any assignment or quiz activity that is tagged with the `Activity tag` specified in the admin settings.
5. Setup an API server that will accept an HTTP `POST` request at the address specified in the admin settings (`External API url`).
6.

---

## 📦 Payload Structure

The plugin sends an HTTP POST request with a JSON array to the API URL.
|**Key**|**Type**|**Description**
|--|--|--|
|`gradeid`|String| The ID of the grade item.
|`coursename`|String| Name of the activity's course.
|`groupname`|String| Name of the user's group.
|`userid`|String|user's ID.
|`firstname`|String|User's first name.
|`lastname`|String|User's last name.
| `activitytype`| String| Type of activity (`assignment` or `quiz`).
|`activityname`|String|The name of the activity.
|`grade_percent`|String|user's grade out of 100.
|`duedate`|String|The activity's submission deadline.
|`submissiondate`|String|The user's submission date
|`submission_status`|String|The submission status. `on time`, `late`, or `missed`.

Fields may be null if not applicable

**Example of the payload:**

JSON

````
[
    {
        "gradeid": "10",
        "coursename": "Programming Foundations",
        "groupname": "jozi26",
        "userid": "4",
        "firstname": "jack",
        "lastname": "Mambo",
        "activitytype": "quiz",
        "activityname": "Quiz: Check your knowledge",
        "grade_percent": "80.00",
        "duedate": "1769603100",
        "submissiondate": "1768907803",
        "submission_status": "on time"
  }
]```
````
