############################################################################
#
# adamysql.py
# Read data from DHT 22 sensor and log to MySQL database
#
############################################################################
import MySQLdb  
import sys
import Adafruit_DHT
import time
import setproctitle


# Set process title
setproctitle.setproctitle('[smarthome => dht22_daemon]') 

# MySQL auth
server='localhost'
db='smarthome'
username='sh'
passwd='sh2019'

# Sensor
#sensor='22'
#pin='6'

# Log interval
sleeptime=60

# Parse command line parameters.
sensor_args = { '11': Adafruit_DHT.DHT11,
                '22': Adafruit_DHT.DHT22,
                '2302': Adafruit_DHT.AM2302 }
if len(sys.argv) == 3 and sys.argv[1] in sensor_args:
    sensor = sensor_args[sys.argv[1]]
    pin = sys.argv[2]
else:
    print('Usage: sudo ./Adafruit_DHT.py [11|22|2302] <GPIO pin number>')
    print('Example: sudo ./Adafruit_DHT.py 2302 4 - Read from an AM2302 connected to GPIO pin #4')
    sys.exit(1)
    

# [debug]
#temp = 15.013276
#hum = 75.372846
#val = (temp, hum)
#print("Temperatur :", round(temp))
#print("Luftfeuchtigkeit :", round(hum))


# Open MySQL connection
try:
	db = MySQLdb.connect(host=server, user=username, passwd=passwd, db=db)
except MySQLdb.Error, e:
	print "Error: %d: %s" % (e.args[0], e.args[1])
	sys.exit(1)
				
# db cursor
cursor=db.cursor()


# Loop forever
while True:
		# Call sensor
		hum, temp = Adafruit_DHT.read_retry(sensor, pin)	
		val=(temp, hum)
		
		if hum is not None and temp is not None:
			# Insert data to MySQL
			try:
				tmp = "INSERT INTO `dht22` (temp, hum) VALUES (%s, %s)"
				cursor.execute(tmp, val)
				db.commit()
			except MySQLdb.Error, e:
				print "Error: %d: %s" % (e.args[0], e.args[1])
				db.rollback()
				
			# Output
			print('Temperature={0:0.1f}*  Humidity={1:0.1f}%'.format(temp, hum))
		
		else:
			print('Failed to get reading. Try again!')
			sys.exit(1)
			
		# Set sleeptime
		time.sleep(sleeptime)
	  
	