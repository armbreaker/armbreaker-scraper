# server mock for frontend development

from flask import Flask, send_from_directory, redirect
app = Flask(__name__)

@app.route("/api/fics/<ficid>")
def getfic(ficid=None):
	if ficid == "549176":
		return send_from_directory(".", "testdata-fault.json")
	if ficid == "517894":
		return send_from_directory(".", "testdata-ringmaker.json")
	if ficid == "304558":
		return send_from_directory(".", "testdata-gently.json")
	if ficid == "560830":
		return send_from_directory(".", "testdata-nimrod.json")
	return "The fic API endpoint."

@app.route("/js/<js>")
def js(js=None):
    return send_from_directory("dist/", js)

@app.route("/css/<css>")
def css(css=None):
    return send_from_directory("dist/", css)

@app.route("/viewer/<ficid>")
def viewer(ficid=None):
    return send_from_directory("dist", "main.html")

# redirects to the testing site for convenience
@app.route("/fault")
def reroute_foolt():
	return redirect("/viewer/549176", code=302)

# redirects to the testing site for convenience
@app.route("/ringmaker")
def reroute_ringmaker():
	return redirect("/viewer/517894", code=302)

# redirects to the testing site for convenience
@app.route("/gently")
def reroute_gently():
	return redirect("/viewer/304558", code=302)

# redirects to the testing site for convenience
@app.route("/nimrod")
def reroute_nimrod():
	return redirect("/viewer/560830", code=302)
