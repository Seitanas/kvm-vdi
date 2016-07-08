import gtk
import webkit
import json
from ConfigParser import ConfigParser
import requests
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
from PyQt4 import QtGui
import sys

config = ConfigParser()
config.read('/usr/local/VDI-client/config')
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
	reply=http_session.post(dashboard_path+"client_pools.php", data={'username': str(self.User.text()), 'password': str(self.Pass.text())}, verify=False)
	if reply.text!='LOGIN_FAILURE':
	    v.connect("notify::title", pool_click)
	    v.load_html_string(str(reply.text),dashboard_path)
	    self.accept()
        else:
	    QtGui.QMessageBox.warning(self, 'Error', 'Bad user or password')

def pool_click(v, param):
    if not v.get_title():
        return
    if v.get_title().startswith("kvm-vdi-msg:"):
	reply=http_session.get(dashboard_path+"client_pools.php");
	v.load_html_string(str(reply.text),dashboard_path)
        message = v.get_title().split(":",1)[1]
        print message

app = QtGui.QApplication(sys.argv)
login = Login()
if login.exec_() == QtGui.QDialog.Accepted:
    w.show_all()
    gtk.main()


