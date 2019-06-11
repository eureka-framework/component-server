#!/bin/bash

. "/lib/lsb/init-functions"

#############################
# Daemon start function
#
# @return void
#############################
d_start () {
    log_daemon_msg "Starting system '${DAEMON_NAME}' Daemon"

    start-stop-daemon \
        --oknodo \
        --start \
        --background \
        --pidfile ${DAEMON_PID} \
        --make-pidfile \
        --user ${DAEMON_USER} \
        --startas ${DAEMON_SCRIPT} \
        -- ${DAEMON_SCRIPT_ARGS}

    log_end_msg $?
}

#############################
# Daemon stop function
#
# @return void
#############################
d_stop () {
    log_daemon_msg "Stopping system '${DAEMON_NAME}' Daemon"

    start-stop-daemon \
        --oknodo \
        --stop \
        --retry 5 \
        --pidfile ${DAEMON_PID}

    log_end_msg $?
}
