#!/usr/bin/env python
# =====================================================================
# Nom           : backupMySQL.py
# Fonction      : Sauvegarde des bases MySQL
# Usage         : Voir fonction help()
# =====================================================================
# Quand         Qui     Quoi
# 20190121      QLA  Creation

#Import des librairies utiles au script
import argparse
import logging
import yaml
import os
import glob
import time
import subprocess

#Definition de variables
#Niveau de verbosite CRITICAL ERROR WARNING INFO DEBUG NOTSET
levelVerbosity = logging.NOTSET
parametersSymfony = "/var/www/www.lairdubois.fr/app/config/parameters.yml"
cmdMysqldump = "/usr/bin/mysqldump"
backupDir = "/Backups"
numberOfBackup = 15

###
### Fonction d'impression des messages de debug
###
def debug(logLevel, message, niveau="info"):
  log = logging.getLogger(__name__)
  #Formatage sortie de log
  #Niveau de verbosite CRITICAL ERROR WARNING INFO DEBUG NOTSET
  log.setLevel(logLevel)
  formatLog = logging.StreamHandler()
  formatLog.setLevel(logLevel)
  formatLog.setFormatter(logging.Formatter('%(asctime)s - %(levelname)s - %(message)s'))
  log.addHandler(formatLog)
  if niveau == "info":
    log.info(message)
  elif niveau== "warning":
    log.warning(message)
  elif niveau== "error":
    log.error(message)
  elif niveau== "debug":
    log.debug(message)
  else:
    log.debug(message)
  log.handlers.pop()

###
### Analyse des arguments passes au script
###
def analyseArgs():
  global levelVerbosity

  parser = argparse.ArgumentParser()
  parser.add_argument('-d','--debug', action='store_true', help='Active le mode debug')
  args = parser.parse_args()
  if args.debug:
    levelVerbosity = logging.DEBUG
  return args

###
### Fonction de recuperation des infos de connexion de la base
###
def databaseParams(ymlFile):
	database = { 'host': '', 'port': '', 'name': '', 'user': '', 'password': '' }
	debug(levelVerbosity,"Recherche des parametres de base dans "+ymlFile,"debug")
	with open(ymlFile, 'r') as stream:
		try:
			yamlParameters = yaml.load(stream)
			database['host'] = yamlParameters['parameters']['database_host']
			database['port'] = str(yamlParameters['parameters']['database_port'])
			database['name'] = yamlParameters['parameters']['database_name']
			database['user'] = yamlParameters['parameters']['database_user']
			database['password'] = yamlParameters['parameters']['database_password']
			debug(levelVerbosity, "Parametres recuperes pour la base:\n\tHost: "+database['host']+"\n\tPort: "+database['port']+"\n\tName: "+database['name']+"\n\tUser: "+database['user']+"\n\tPassword: "+str(database['password']))
		except yaml.YAMLError as exc:
			debug(levelVerbosity,exc,"error")
			database = None
	return database

###
### Fonction de Backup des bases
###
def databaseBackup(dictDatabaseParameters,binMysqldump,backupDirectory):
	result = True
	dayDate = time.strftime('%Y%m%d')
	print dayDate
	fileBackup = backupDirectory+"/"+dictDatabaseParameters['name']+"-"+dayDate+".sql"

	debug(levelVerbosity,"Backup de la base "+dictDatabaseParameters['name'],"debug")
	#Test de l'existance du binaire mysqldump
	if os.path.isfile(binMysqldump):
		debug(levelVerbosity,"Le binaire mysqldump existe bien","debug")
		if os.path.isdir(backupDirectory):
			debug(levelVerbosity,"Le repertoire de backup existe bien","debug")
			debug(levelVerbosity,"Generation de la commande de backup","debug")
			dumpCommand = binMysqldump + " -h " + dictDatabaseParameters['host'] + " -P " + dictDatabaseParameters['port']
			dumpCommand = dumpCommand + " -u " + dictDatabaseParameters['user']
			if dictDatabaseParameters['password'] is not None:
				dumpCommand = dumpCommand + " -p" + dictDatabaseParameters['password']
			dumpCommand = dumpCommand + " --opt --databases " + dictDatabaseParameters['name']
			dumpCommand = dumpCommand + " --result-file=" + fileBackup
			debug(levelVerbosity,"Commande de backup generee:\n\t"+dumpCommand,"debug")
			debug(levelVerbosity,"Generation du fichier "+fileBackup,"debug")
			try:
				if dictDatabaseParameters['password'] is not None:
					retValue = subprocess.call([binMysqldump, "-h", dictDatabaseParameters['host'], "-P", dictDatabaseParameters['port'], "-u", dictDatabaseParameters['user'], "-p"+str(dictDatabaseParameters['password']), "--opt", "--databases", dictDatabaseParameters['name'], "--result-file="+fileBackup ])
				else:
					retValue = subprocess.call([binMysqldump, "-h", dictDatabaseParameters['host'], "-P", dictDatabaseParameters['port'], "-u", dictDatabaseParameters['user'], "--opt", "--databases", dictDatabaseParameters['name'], "--result-file="+fileBackup ])
				if retValue == 0:
					debug(levelVerbosity,"La commande de backup s'est correctemnt executee","debug")
					debug(levelVerbosity,"Compression du fichier de backup","debug")
					retValue = subprocess.call(["/bin/gzip", fileBackup])
					if retValue == 0:
						debug(levelVerbosity,"La compression du fichier de backup s'est correctemnt executee","debug")
					else:
						debug(levelVerbosity,"La compression du fichier de backup a echoue","error")
						result = False
				else:
					debug(levelVerbosity,"L'execution de la commande de sauvegarde a echoue","error")
					result = False

			except:
				debug(levelVerbosity,"L'execution de la commande de sauvegarde a echoue","error")
				result = False
		else:
			debug(levelVerbosity,"Le repertoire de backup n'existe pas","error")
			result = False
	else:
		debug(levelVerbosity,"Le binaire mysqldump n'existe pas","error")
		result = False
	return result

###
### Fonction de suppression des anciens backups
###
def removeBackups(baseName, backupDirectory, number):
	result = True

	debug(levelVerbosity,"Suppression des anciens backups","debug")
	listDir = glob.glob(backupDirectory+"/"+baseName+"*")
	listDir.sort(reverse=True)
	debug(levelVerbosity,"Liste des fichiers de backup pour cette base:\n"+str(listDir),"debug")
	filesToRemove = listDir[number:]
	debug(levelVerbosity,"Liste des fichiers a supprimer:\n"+str(filesToRemove),"debug")
	for file in filesToRemove:
		debug(levelVerbosity,"Suppression du fichier : "+file,"debug")
		try:
			os.remove(file)
		except:
			debug(levelVerbosity,"La suppression du fichier "+file+" a echoue","error")
			result = False
	return result

###
### Main
###
args = analyseArgs()
mysqlParameters = databaseParams(parametersSymfony)
if mysqlParameters is not None:
	if databaseBackup(mysqlParameters,cmdMysqldump,backupDir):
		debug(levelVerbosity,"Le backup de la base a reussi","debug")
		if removeBackups(mysqlParameters['name'],backupDir,numberOfBackup):
			debug(levelVerbosity,"La suppression des anciens backups a reussi","debug")
		else:
			debug(levelVerbosity,"La suppression des anciens backups a echoue","error")
	else:
		debug(levelVerbosity,"Le backup de la base a echoue","error")
else:
	debug(levelVerbosity,"Les informations de connexion a la base de donnees sont vide","error")
