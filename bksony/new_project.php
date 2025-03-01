<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Submission Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    body {
        background-color: rgb(220, 222, 225);
    }

    .container {
        max-width: 700px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-top: 40px;
        transition: 1s;
    }

    .container:hover {
        box-shadow: 0px 1px 10px 1px rgba(0, 0, 0);

    }

    .hidden {
        display: none;
    }
    </style>
</head>

<body>

    <div class="container">
        <h3 class="text-center mb-4">Submit Your Project</h3>
        <form id="projectForm">

            <!-- Project Name -->
            <div class="mb-3">
                <label class="form-label">Project Name</label>
                <input type="text" class="form-control" name="project_name" required placeholder="Enter project name">
            </div>

            <!-- Project Type -->
            <div class="mb-3">
                <label class="form-label">Project Type</label>
                <select class="form-select" name="project_type" id="projectType" required
                    onchange="toggleProjectType()">
                    <option value="">Select Type</option>
                    <option value="software">Software</option>
                    <option value="hardware">Hardware</option>
                </select>
            </div>

            <!-- Software Classification -->
            <div class="mb-3 hidden" id="softwareOptions">
                <label class="form-label">Software Classification</label>
                <select class="form-select" name="software_classification">
                    <option value="web">Web Application</option>
                    <option value="mobile">Mobile Application</option>
                    <option value="desktop">Desktop Software</option>
                    <option value="embedded">Embedded Software</option>
                </select>
            </div>

            <!-- Hardware Classification -->
            <div class="mb-3 hidden" id="hardwareOptions">
                <label class="form-label">Hardware Classification</label>
                <select class="form-select" name="hardware_classification">
                    <option value="iot">IoT Device</option>
                    <option value="robotics">Robotics</option>
                    <option value="electronics">Electronics Circuit</option>
                </select>
            </div>

            <!-- Project Description -->
            <div class="mb-3">
                <label class="form-label">Project Description</label>
                <textarea class="form-control" name="description" rows="4" required
                    placeholder="Describe your project"></textarea>
            </div>

            <!-- Programming Language -->
            <div class="mb-3">
                <label class="form-label">Programming Language</label>
                <input type="text" class="form-control" name="language" placeholder="e.g., Python, Java, C++" required>
            </div>

            <!-- Upload Images -->
            <div class="mb-3">
                <label class="form-label">Upload Images</label>
                <input type="file" class="form-control" name="images" multiple accept="image/*">
            </div>

            <!-- Upload Videos -->
            <div class="mb-3">
                <label class="form-label">Upload Videos</label>
                <input type="file" class="form-control" name="videos" multiple accept="video/*">
            </div>

            <!-- Upload Code File -->
            <div class="mb-3">
                <label class="form-label">Upload Code File</label>
                <input type="file" class="form-control" name="code_file" accept=".zip,.rar,.tar,.gz">
            </div>


            <!-- Upload Instruction File -->
            <div class="mb-3">
                <label class="form-label">Upload Instruction File / Report File (Which will help to impliment this
                    project )</label>

                <input type="file" class="form-control" name="instruction_file" accept=".txt,.pdf,.docx">
                <p class="text-danger">
                    Only : .doc , .txt , .pdf will accept

                </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100">Submit Project</button>

        </form>
    </div>

    <script>
    function toggleProjectType() {
        let projectType = document.getElementById("projectType").value;
        document.getElementById("softwareOptions").classList.add("hidden");
        document.getElementById("hardwareOptions").classList.add("hidden");

        if (projectType === "software") {
            document.getElementById("softwareOptions").classList.remove("hidden");
        } else if (projectType === "hardware") {
            document.getElementById("hardwareOptions").classList.remove("hidden");
        }
    }

    document.getElementById("projectForm").addEventListener("submit", function(event) {
        event.preventDefault();
        alert("Project submitted successfully!");
    });
    </script>

</body>

</html>