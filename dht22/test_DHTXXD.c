/*
test_DHTXXD.c
2016-02-16
Public Domain
*/

/*

REQUIRES

One or more DHT11/DHT21/DHT22/DHT33/DHT44.

TO BUILD

gcc -Wall -pthread -o DHTXXD test_DHTXXD.c DHTXXD.c -lpigpiod_if2

TO RUN

./DHTXXD -g17 # one reading from DHT connected to GPIO 17

./DHTXXD -g14 -i3 # read DHT connected to GPIO 14 every 3 seconds

*/

#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <unistd.h>

#include <pigpiod_if2.h>

// Glib keyfile
#include <glib.h>
#include <glib/gprintf.h>

// MySQL
#include <mysql.h>

#include "DHTXXD.h"

void fatal(char *fmt, ...)
{
   char buf[128];
   va_list ap;

   va_start(ap, fmt);
   vsnprintf(buf, sizeof(buf), fmt, ap);
   va_end(ap);

   fprintf(stderr, "%s\n", buf);

   fflush(stderr);

   exit(EXIT_FAILURE);
}

void usage()
{
   fprintf(stderr, "\n"
                   "Usage: DHTXXD [OPTION] ...\n"
                   "   -g value, gpio, 0-31,                       default 4\n"
                   "   -i value, reading interval in seconds\n"
                   "             0=single reading,                 default 0\n"
                   "   -m value, model 0=auto, 1=DHT11, 2=other,   default auto\n"
                   "   -h string, host name,                       default NULL\n"
                   "   -p value, socket port, 1024-32000,          default 8888\n"
                   "EXAMPLE\n"
                   "DHTXXD -g11 -i5\n"
                   "   Read a DHT connected to GPIO 11 every 5 seconds.\n\n");
}

int optGPIO = 4;
char *optHost = NULL;
char *optPort = NULL;
int optModel = DHTAUTO;
int optInterval;
int runInterval;

static uint64_t getNum(char *str, int *err)
{
   uint64_t val;
   char *endptr;

   *err = 0;
   val = strtoll(str, &endptr, 0);
   if (*endptr)
   {
      *err = 1;
      val = -1;
   }
   return val;
}

// Print usage
static void initOpts(int argc, char *argv[])
{
   int opt, err, i;

   while ((opt = getopt(argc, argv, "g:h:i:I:m:p:")) != -1)
   {
      switch (opt)
      {
      case 'g':
         i = getNum(optarg, &err);
         if ((i >= 0) && (i <= 31))
            optGPIO = i;
         else
            fatal("invalid -g option (%d)", i);
         break;

      case 'h':
         optHost = malloc(sizeof(optarg) + 1);
         if (optHost)
            strcpy(optHost, optarg);
         break;

      case 'i':
         i = getNum(optarg, &err);
         if ((i >= 0) && (i <= 86400))
            optInterval = i;
         else
            fatal("invalid -i option (%d)", i);
         break;

      case 'I':
         i = getNum(optarg, &err);
         // Read sensor minimum every 3 seconds
         if ((i >= 3) && (i <= 86400))
            runInterval = i;
         else
            fatal("invalid -I option (%d). Intervall nust be more than 3 seconds!", i);
         break;

      case 'm':
         i = getNum(optarg, &err);
         if ((i >= DHTAUTO) && (i <= DHTXX))
            optModel = i;
         else
            fatal("invalid -m option (%d)", i);
         break;

      case 'p':
         optPort = malloc(sizeof(optarg) + 1);
         if (optPort)
            strcpy(optPort, optarg);
         break;

      default: /* '?' */
         usage();
         exit(-1);
      }
   }
}

// Callback
void cbf(DHTXXD_data_t r)
{
   printf("%d %.1f %.1f\n", r.status, r.temperature, r.humidity);

   GKeyFile *keyfile;
   GKeyFileFlags flags;
   GError *error = NULL;
   MYSQL *my;

   char *sql_servername;
   char *sql_user;
   char *sql_passwd;
   char *sql_db;
   char *sql_table;
   char query[255];
   int gpio;

   /**
    * GKeyFile
    * 
    * Get key pair values fron dht22.ini
    */
   // Create a new GKeyFile object and a bitwise list of flags.
   keyfile = g_key_file_new();
   flags = G_KEY_FILE_KEEP_COMMENTS | G_KEY_FILE_KEEP_TRANSLATIONS;

   // Load the GKeyFile from keyfile.conf or return.
   if (!g_key_file_load_from_file(keyfile, "dht22.ini", flags, &error))
      exit(1);

   // Get values
   sql_servername = g_key_file_get_string(keyfile, "dht22", "sql_server", NULL);
   sql_user = g_key_file_get_string(keyfile, "dht22", "sql_username", NULL);
   sql_passwd = g_key_file_get_string(keyfile, "dht22", "sql_passwd", NULL);
   sql_db = g_key_file_get_string(keyfile, "dht22", "sql_db", NULL);
   sql_table = g_key_file_get_string(keyfile, "dht22", "sql_table", NULL);
   gpio = g_key_file_get_integer(keyfile, "dht22", "gpio", NULL);

   // Print values
   printf("sql_servername: %s\n", sql_servername);
   printf("sql_user: %s\n", sql_user);
   printf("sql_passwd: %s\n", sql_passwd);
   printf("sql_db: %s\n", sql_db);
   printf("sql_table: %s\n", sql_table);
   printf("gpio: %d\n", gpio);

   /**
    * MySQL
    * 
    * Store dht22 values in database
    */
   // Initialize MySQL
   my = mysql_init(NULL);
   if (my == NULL)
   {
      fprintf(stderr, "%s\n", mysql_error(my));
      exit(1);
   }

   /* mit dem Server verbinden */
   if (mysql_real_connect(
           my,             /* Zeiger auf MYSQL-Handler */
           sql_servername, /* Host-Name */
           sql_user,       /* User-Name */
           sql_passwd,     /* Passwort f�r user_name */
           sql_db,         /* Name der Datenbank */
           0,              /* Port (default=0) */
           NULL,           /* Socket (default=NULL) */
           0 /* keine Flags */) == NULL)
   {
      fprintf(stderr, 
         "Fehler mysql_real_connect():" "%u (%s)\n",
         mysql_errno(my),
         mysql_error(my)
      );
   }

   // Build query string
   sprintf(query, "INSERT INTO `%s` (temp, hum) VALUES (%f, %f)", sql_table, r.temperature, r.humidity);

   // [Debug]
   //printf(query);

   // Execute query
   if (mysql_query(my, query))
   {
      fprintf(stderr, "%s\n", mysql_error(my));
      mysql_close(my);
      exit(1);
   }

   // Close connection
   mysql_close(my);
}

int main(int argc, char *argv[])
{
   int pi;
   DHTXXD_t *dht;

   initOpts(argc, argv);

   pi = pigpio_start(optHost, optPort); /* Connect to local Pi. */

   if (pi >= 0)
   {
      dht = DHTXXD(pi, optGPIO, optModel, cbf); /* Create DHTXX. */
      
      if (optInterval)
      {
         DHTXXD_auto_read(dht, optInterval);

         while (1) time_sleep(60);
      }
      else
      {
         DHTXXD_manual_read(dht);
      }    

      DHTXXD_cancel(dht); /* Cancel DHTXX. */

      pigpio_stop(pi); /* Disconnect from local Pi. */
   }
   return 0;
}
