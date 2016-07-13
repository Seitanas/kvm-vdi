@echo off
echo "Building the executable"
python setup.py py2exe -b 1
copy ..\configurations\default.ini default.ini
copy ..\configurations\default-logger.ini default-logger.ini
copy ..\configurations\ovirt-guest-agent.ini ovirt-guest-agent.ini
