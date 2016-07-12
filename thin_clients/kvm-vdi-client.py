import gtk
import webkit
import json
from ConfigParser import ConfigParser
import requests
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
from PyQt4 import QtGui
import sys
import threading
from random import choice
from string import ascii_uppercase
from string import ascii_lowercase
from string import digits
import os
import time

config = ConfigParser()
#config.read('/usr/local/VDI-client/config')
config.read('/home/seitan/projects/kvm-vdi-client/config')
dashboard_path = config.get('server', 'address')


w = gtk.Window()
v = webkit.WebView()
v.props.settings.props.enable_default_context_menu = False
sw = gtk.ScrolledWindow()
w.add(sw)
sw.add(v)
w.maximize()
w.connect("destroy", lambda q: gtk.main_quit())
http_session = requests.session()
username=""
password=""

class Login(QtGui.QDialog):
    def __init__(self, parent=None):
        super(Login, self).__init__(parent)
        self.User = QtGui.QLineEdit(self)
        self.Pass = QtGui.QLineEdit(self)
        self.Pass.setEchoMode(QtGui.QLineEdit.Password)
        self.buttonLogin = QtGui.QPushButton('Login', self)
        self.buttonLogin.clicked.connect(self.handleLogin)
        layout = QtGui.QVBoxLayout(self)
        layout.addWidget(self.User)
        layout.addWidget(self.Pass)
        layout.addWidget(self.buttonLogin)

    def handleLogin(self):
	global username
	global password
	reply=http_session.post(dashboard_path+"client_pools.php", data={'username': str(self.User.text()), 'password': str(self.Pass.text())}, verify=False)
	if reply.text!='LOGIN_FAILURE':
	    username=str(self.User.text())
	    password=str(self.Pass.text())
	    v.connect("notify::title", pool_click)
	    v.load_html_string(str(reply.text),dashboard_path)
	    self.accept()
        else:
	    QtGui.QMessageBox.warning(self, 'Error', 'Bad user or password')



class vm_heartbeat(threading.Thread):

    def __init__(self, vmname):
        super(vm_heartbeat, self).__init__()
        self._stop = threading.Event()
	self.vmname = vmname

    def stop(self):
        self._stop.set()

    def stopped(self):
        return self._stop.isSet()
    
    def run(self):
        while not self.stopped():
            print self.vmname
	    hb_reply=http_session.post(dashboard_path+"client_hb.php", data={'vmname': self.vmname}, verify=False)
            time.sleep(25)

def dashboard_reload():
    page=http_session.get(dashboard_path+"client_pools.php", verify=False)
    v.load_html_string(str(page.text),dashboard_path)

def pool_click(v, param):
    if not v.get_title():
        return
    if v.get_title().startswith("kvm-vdi-msg:"):
	vdi_message=v.get_title().replace("kvm-vdi-msg:","")
	if vdi_message.startswith("PM:"):
	    vdi_message=vdi_message.replace("PM:","")
	    vdi_PM=vdi_message.split(":")
	    if vdi_PM[0]=="shutdown":
		PM_reply=http_session.post(dashboard_path+"client_power.php", data={'vm': vdi_PM[1], 'action': "shutdown"}, verify=False)
	    if vdi_PM[0]=="destroy":
		PM_reply=http_session.post(dashboard_path+"client_power.php", data={'vm': vdi_PM[1], 'action': "destroy"}, verify=False)
	    dashboard_reload()
	else:
	    poolid=vdi_message
	    reply=http_session.post(dashboard_path+"client.php", data={'pool': poolid, 'protocol': "SPICE", 'username': username, 'password': password} ,verify=False);
	    print reply.text
	    data=json.loads(reply.text)
	    if data['status']=='MAINTENANCE':
		QtGui.QMessageBox.information(login, 'Information', 'This pool is in maintenance mode')
	    retries=0
	    while data['status']=="BOOTUP" and retries < 10 :
		print "BOOTUP, waiting"
		reply=http_session.post(dashboard_path+"client.php", data={'pool': poolid, 'protocol': "SPICE", 'username': username, 'password': password} ,verify=False);
		data=json.loads(reply.text)
		time.sleep(1)
	    if data['status']=='OK':
#		page=http_session.get(dashboard_path+"client_pools.php", verify=False)
#		v.load_html_string(str(page.text),dashboard_path)
		dashboard_reload()
		print "OK, starting HB thread & viewer"
		vmname=data['name']
		t = vm_heartbeat(vmname)
		t.daemon=True
		t.start()
		tmp=data["address"].split(":")
    		spice_password=data["spice_password"]
		tmpname=(''.join(choice(ascii_uppercase+ascii_lowercase+digits) for i in range(13)))
		viewer_config=ConfigParser()
    		viewer_config.add_section('virt-viewer')
    		viewer_config.set('virt-viewer', 'type', 'spice')
    		viewer_config.set('virt-viewer', 'host', tmp[0])
    		viewer_config.set('virt-viewer', 'port', tmp[1])
    		viewer_config.set('virt-viewer', 'delete-this-file', '1')
    		viewer_config.set('virt-viewer', 'password', spice_password)
    		with open('/tmp/' + tmpname + '.cfg', 'wb') as configfile:
		    viewer_config.write(configfile)
		os.system("/usr/bin/remote-viewer /tmp/" + tmpname + ".cfg ")
		print "Exiting virt-viewer"
		w.set_title("kvm-vdi-msg:")
		t.stop()
		t.join(1)

app = QtGui.QApplication(sys.argv)
login = Login()
if login.exec_() == QtGui.QDialog.Accepted:
    w.show_all()
    gtk.main()


