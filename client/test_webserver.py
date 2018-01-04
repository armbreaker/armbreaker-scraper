# server mock for frontend development

from flask import Flask, send_from_directory, redirect
app = Flask(__name__, static_folder=".", static_url_path="/static")

@app.route("/api/fics/<ficid>")
def getfic(ficid=None):
	if ficid == "549176":
		return send_from_directory(".", "testdata-fault.json")
	if ficid == "517894":
		return send_from_directory(".", "testdata-ringmaker.json")
	return "The fic API endpoint."

@app.route("/viewer/<ficid>")
def viewer(ficid=None):
    return send_from_directory("dist", "main.html")

# redirects to the testing site for convenience
@app.route("/")
def reroute():
	return redirect("/viewer/549176", code=302)